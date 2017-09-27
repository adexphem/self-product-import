<?php

namespace App\Http\Controllers;


use Auth;
use App\Site;
use App\User;
use App\Source;
use \Firebase\JWT\JWT;
use App\ProductMapping;
use Illuminate\Http\Request;
use App\Contracts\Connector;
use GuzzleHttp\Client as GuzzleClient;
use App\Redis\Client as RedisClient;
use App\Repositories\WeeblyRepository;
use App\Repositories\SitesRepository;
use App\Repositories\SourcesRepository;
use App\Helpers\SyncActivityLog as Synclog;


class SourcesController extends Controller
{
    public $weeblyRepository;
    public $logger;

    public function __construct (WeeblyRepository $weeblyRepository, Synclog $logger) {
        $this->weeblyRepository = $weeblyRepository;
        $this->logger = $logger;
    }

    public function index(Request $request, Connector $sourceConnector) {
        $sourceName = $sourceConnector->name;

        return view('source.dashboard', [
            'jwt' => $request->jwt,
            'source' => $sourceName
        ]);
    }

    /**
     * initiateSync - renders display for the Sync button page
     *
     * @param object $sourceConnector
     * @return void
    */
    public function initiateSync(Request $request, Connector $sourceConnector) {
        $sourceName = $sourceConnector->name;
        $viewData = [];
        $url = route('source.sync', [ "source_connector" => $sourceName, "jwt" => $request->jwt ]);

        switch($sourceName) {
            case 'mindbody':
                $viewLink = "source.". $sourceName .".sync_product";
                $viewData = [
                    "syncedProducts" => $request->syncedProducts ?? "0",
                    "lastSyncedDate" => $request->lastSyncedDate ?? "Never"
                ];
                break;
            case 'shapeways':
                $viewLink = "source.sync_product";
                break;
        }

        $logData = $this->logger->formatLogData($request, $sourceName, Synclog::SOURCE_PRODUCT_IMPORT_INITIATION_ACTION,
            Synclog::SOURCE_PRODUCT_IMPORT_INITIATION_STATUS);
        $this->logger->log($logData);

        return view($viewLink, array_merge([
            "sourceConnector" => $sourceName,
            "syncLink" => $url
        ], $viewData));
    }

    public function sync (Request $request, Connector $sourceConnector, GuzzleClient $guzzleClient, RedisClient $redisClient) {
        $decoded = $this->weeblyRepository->decodeUserInfo($request->jwt, $sourceConnector);
        $key = $redisClient->composeKey($decoded["site_id"],$decoded["user_id"]);
        $site = SitesRepository::getSiteBySiteId($decoded["site_id"]);
        $source = SourcesRepository::getSourceBySiteId($decoded["site_id"]);

        $request->request->add([
            'site_id' => $decoded["site_id"],
            'user_id' => $decoded["user_id"]
        ]);

        $syncMsg = "No new product(s) to be synchronized.";


        switch ($sourceConnector->name) {
            case 'shapeways':
                $token = json_decode($source->credentials)->access_token;
                $response = $sourceConnector->getProducts($guzzleClient, $token);
                $products = collect($this->weeblyRepository->getResponseBody($response)->{$sourceConnector->productFieldName})
                    ->map(function ($product) use ($sourceConnector, $guzzleClient, $token) {
                        return $this->weeblyRepository->getResponseBody($sourceConnector->getFullProducts($guzzleClient, $product->{$sourceConnector->productIdFieldName}, $token));
                    });
                break;
            case 'mindbody':
                $siteIds = [ $sourceConnector->getStudioIdFromSource($source) ];
                $response = $sourceConnector->getProducts($siteIds);
                $products = ! is_null($response)
                    ? collect($sourceConnector->getDataFromResponse($response)->Products->Product)
                    : collect([]);

                $logData = $this->logger->formatLogData($request, $sourceConnector->name, Synclog::SOURCE_PRODUCT_IMPORT_ACTION,
                    Synclog::SOURCE_PRODUCT_IMPORT_STATUS);
                $this->logger->log($logData);

                break;
             default:
                $products = collect([]);
                break;
        }

        foreach ($products as $product) {

            $weeblyProductData = $sourceConnector->mapProduct($product);

            if (! ProductMapping::doesProductExist($product->ID)) {
                $productCreateResult = $this->weeblyRepository->createProduct($guzzleClient, $weeblyProductData,  $key, $redisClient);

                if($productCreateResult->status) {
                    $source->productMappings()->create([
                        'source_product_id' => $product->ID,
                        'weebly_product_id' => $productCreateResult->weebly_product_id
                    ]);
                }
            } else {
                $weeblyProductId = $this->weeblyRepository->getWeeblyProductIDFromSourceMapping($source, $product->ID);
                if ($weeblyProductData) {
                    $weeblyProductUpdate = $sourceConnector->mapProductForUpdate($product, $site, $weeblyProductId); // refactor - product might be published or taxable.
                    $updateResponse = $this->weeblyRepository->updateProduct($guzzleClient, $weeblyProductUpdate, $site, $weeblyProductId);
                    $weeblyProductUpdated = $this->weeblyRepository->getResponseBody($updateResponse);
                }
            }
        }

        $source->saveCount();

        $numberOfProductsSynced = $source->synced_products_no;

        //@todo convert this date formatting to a method/helper
        $productsSyncedOn = $source->updated_at->format('l, F jS, Y');

        $syncMsg = ($numberOfProductsSynced > 0) ? $numberOfProductsSynced ." new product(s) were synchronized successfully." : $syncMsg;
        $update = $sourceConnector->dashboardSyncedCard($numberOfProductsSynced, url("/"));
        $this->weeblyRepository->updateDashboardCard($guzzleClient, $site, $sourceConnector->cardId, $update);

        switch ($sourceConnector->name) {
            case 'shapeways':
                return redirect()->route('source.start.sync', $sourceConnector->name)->with('sync_msg', $syncMsg);
                break;
            case 'mindbody':
                return response()->json([
                    'productsSyncedCount' => $numberOfProductsSynced,
                    'lastSyncedDate' => $productsSyncedOn
                ]);

                break;
        }

	}
}