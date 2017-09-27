<?php

namespace App\Http\Controllers\Auth;

use Auth;
use App\Site;
use App\Source;
use App\User;
use App\Contracts\Connector;
use \Firebase\JWT\JWT;
use Illuminate\Http\Request;
use App\Http\Requests\MindbodyActivationRequest;
use App\Http\Controllers\Controller;
use App\Redis\Client as RedisClient;
use App\Repositories\SitesRepository;
use App\Repositories\UsersRepository;
use App\Repositories\WeeblyRepository;
use App\Repositories\SourcesRepository;
use GuzzleHttp\Client as GuzzleClient;
use App\Helpers\SyncActivityLog as Synclog;
use App\Exceptions\InvalidHmacException;

class OAuthController extends Controller
{
    public $weeblyRepository;
    public $logger;

    public function __construct (WeeblyRepository $weeblyRepository, Synclog $logger) {
        $this->weeblyRepository = $weeblyRepository;
        $this->logger = $logger;
    }

    public function phaseOne (Request $request, Connector $sourceConnector) {
        $hmacParams = $this->weeblyRepository->prepareParams($request->all());

        if(!($this->weeblyRepository->validateHMAC(http_build_query($hmacParams), $request->hmac, $sourceConnector))) {
            $logData = $this->logger->formatLogData($request, Synclog::SOURCE_TYPE,
                Synclog::WEEBLY_PHASE_ONE_ACTION, Synclog::HMAC_INVALID);
            $this->logger->log($logData);

            throw new InvalidHmacException();
        }

        SitesRepository::updateOrCreate($hmacParams["site_id"], $hmacParams["user_id"]);
        $logData = $this->logger->formatLogData($request, Synclog::SOURCE_TYPE,
            Synclog::WEEBLY_PHASE_ONE_ACTION, Synclog::HMAC_VALID);
        $this->logger->log($logData);

        return redirect()->to($this->weeblyRepository->getCallbackQuery($request->all(), $sourceConnector));
    }

    public function phaseTwo (Request $request, GuzzleClient $guzzleClient, $sourceConnector) {

        $response = $this->weeblyRepository->getAcessToken($guzzleClient, $request->authorization_code, $sourceConnector);
        $responseBody = $this->weeblyRepository->getResponseBody($response);

        $logData = $this->logger->formatLogData($request, Synclog::SOURCE_TYPE,
            Synclog::WEEBLY_PHASE_TWO_ACTION, Synclog::ACCESS_TOKEN_GENERATED);
        $this->logger->log($logData);

        SitesRepository::updateOrCreate($request->site_id, $request->user_id, $responseBody->access_token);
        UsersRepository::firstOrCreate($request->site_id, $request->user_id);

        return redirect()->to($responseBody->callback_url);
    }

    public function connect (Request $request, Connector $sourceConnector, RedisClient $redisClient) {
        $userInfo = $this->weeblyRepository->decodeUserInfo($request->jwt, $sourceConnector);
        $redisKey = $userInfo["site_id"].$userInfo["user_id"];
        $request->request->add([
            'site_id' => $userInfo["site_id"],
            'user_id' => $userInfo["user_id"]
        ]);
        $sourceName = $sourceConnector->name;

        switch ($sourceName) {
            case 'shapeways':
                $user = UsersRepository::firstOrCreate($userInfo["site_id"], $userInfo["user_id"]);
                Auth::login($user);
                $redisClient->updateCertainPath($redisKey, "weebly_jwt", $request->jwt);
                return redirect()->to($sourceConnector->getAuthorizationUrl());
            case 'mindbody':
                $source = SourcesRepository::firstOrCreateSource($request->site_id, $sourceName);
                $studioId = $sourceConnector->getStudioIdFromSource($source);
                $syncedProductsCount = $source->synced_products_no;
                $lastSyncedOn = $source->updated_at->format('l, F jS, Y');
                $activationStatus = $source->activation_status ?? SourcesRepository::DISCONNECTED_SOURCE;

                $logData = $this->logger->formatLogData($request,  $sourceName,
                    "Connecting to {$sourceName} site", $activationStatus);
                $this->logger->log($logData);

                switch ($activationStatus) {
                    case (SourcesRepository::CONNECTED_SOURCE):
                        return redirect()->route("source.start.sync", [
                            "sourceConnector" => $sourceName,
                            "jwt" => $request->jwt,
                            "studioId" => $studioId,
                            "syncedProducts" => $syncedProductsCount,
                            "lastSyncedDate" => $lastSyncedOn,
                            "site_id" => $request->site_id,
                            "user_id" => $request->user_id
                        ]);
                    case (SourcesRepository::DISCONNECTED_SOURCE):
                        return view('source.mindbody.studioid', [
                            "sourceConnector" => $sourceName,
                            "jwt" => $request->jwt,
                            "studioId" => $studioId
                        ]);
                    default:
                        break;
                }

            default:
                break;
        }
    }

    public function activationCode (MindbodyActivationRequest $request, $sourceConnector) {
        $studioId = $request->studio_id;
        $jwt = $request->jwt;
        $activationCodeResponse = $sourceConnector->getActivationCode([ $studioId ]);
        if (! is_null($activationCodeResponse)) {
            $activationLink = $activationCodeResponse->ActivationLink;
            $sourceConnector = $sourceConnector->name;
            return view('source.mindbody.activate', [
                "activationLink" => $activationLink,
                "sourceConnector" => $sourceConnector,
                "studioId" => $studioId,
                "jwt" => $jwt
            ]);
        } else {
            return view('source.mindbody.studioid', [
                "sourceConnector" => $sourceConnector->name,
                "jwt" => $jwt,
                "studioId" => $studioId
                // @todo - add a flash message
            ]);
        }
    }

    public function sourceConnectCallback (Request $request, $sourceConnector, GuzzleClient $guzzleClient, RedisClient $redisClient) {
        $redisKey = $redisClient->composeKey(Auth::user()->weebly_site_id, Auth::user()->weebly_user_id);

        $source = Source::updateOrCreate([
            'site_id' => Auth::user()->weebly_site_id,
            'type' => $sourceConnector->name
        ]);

        switch ($sourceConnector->name) {
            case 'shapeways':
                $data = $sourceConnector->getAccessToken($request->code, $guzzleClient);
                $response = $this->weeblyRepository->getResponseBody($data);
                $source->update([ 'credentials' => json_encode($response) ]);

                $redisData = $redisClient->find($redisKey);
                $redisClient->updateCertainPath($redisKey, "source_access_token", $response->access_token);
                $redisClient->updateCertainPath($redisKey, "source_id", $source->id);
                return redirect()->route("source.start.sync", $sourceConnector->name)
                    ->with('jwt', $redisData['weebly_jwt']);
            default:
                break;
        }
    }

    public function finishConnection (Request $request, $sourceConnector, GuzzleClient $guzzleClient) {
        $decoded = $this->weeblyRepository->decodeUserInfo($request->jwt, $sourceConnector);
        switch ($sourceConnector->name) {
            case 'mindbody':
                // @TODO
                // check that souce is authenticated at MB
                // if authenticated
                
                $source = SourcesRepository::getSourceBySiteId($decoded["site_id"]);
                $site = SitesRepository::getSiteBySiteId($decoded["site_id"]);

                // update connection field.
                $studioId = $request->studio_id;
                $updated = $sourceConnector->activateSource($source, $sourceConnector->makeCredentials($studioId));

                // update dashboard card with synced products number
                if ($updated) {
                    $numberOfProductsSynced = $source->synced_products_no;
                    $update = $sourceConnector->dashboardSyncedCard($numberOfProductsSynced, url("/"));
                    $response = $this->weeblyRepository->updateDashboardCard($guzzleClient, $site, $sourceConnector->getDashboardCardId(), $update);
                    $newDashboardCard = $this->weeblyRepository->getResponseBody($response);
                }

                // redirect to synced page.
                return redirect()->route("source.start.sync", [
                    "sourceConnector" => $sourceConnector->name,
                    "jwt" => $request->jwt,
                    "studioId" => $studioId,
                    "site_id" => $decoded["site_id"],
                    "user_id" => $decoded["user_id"]
                ]);
            default:
                break;
        }
    }

    public function error()
    {
        return view('errors.oauth');
    }
}
