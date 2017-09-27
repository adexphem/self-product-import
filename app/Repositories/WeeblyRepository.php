<?php

namespace App\Repositories;

use App\Contracts\Connector;
use \Firebase\JWT\JWT;
use App\Redis\Client as RedisClient;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Stream as GuzzleStream;
use GuzzleHttp\Psr7\Response as GuzzleHttpResponse;
use Mockery\Exception;
use App\Site;
use App\Source;
use App\ProductMapping;
use App\Services\WeeblyProductUpdateService;

class WeeblyRepository {

    const WEEBLY_ACCEPT_HEADER = "application/vnd.weebly.v1+json";
    const WEEBLY_CONTENT_TYPE_HEADER = "application/json";

    public static $apiHost = "https://api.weebly.com";

    public function validateHMAC (string $payload, string $hash, Connector $sourceConnector) : bool {
        $verification_hash = hash_hmac('sha256', $payload, $sourceConnector->clientSecret, false);
        return ($verification_hash == $hash);
	}

	public function prepareParams (array $requestParams) : array {
		$hmacParams = [
            'user_id' => $requestParams["user_id"],
            'timestamp' => $requestParams["timestamp"]
        ];
        if ($requestParams["site_id"]) {
            $hmacParams["site_id"] = $requestParams["site_id"];
        }
        return $hmacParams;
	}

	public function getCallbackQuery (array $requestParams, Connector $sourceConnector) : string {
		return $requestParams["callback_url"] . "?" . http_build_query([
			'client_id' => $sourceConnector->clientId,
            'user_id' => $requestParams["user_id"],
            'timestamp' => $requestParams["timestamp"],
            'site_id' => $requestParams["site_id"],
            'version' => $requestParams["version"],
            'scope' => 'read:store-catalog,write:store-catalog',
            'redirect_uri' => url('/') . "/auth/" . $sourceConnector->name . "/phasetwo"
		]);
	}

    public function getAcessToken (GuzzleClient $client, string $authorizationCode, Connector $sourceConnector) : GuzzleHttpResponse {
        return $client->request('POST', env("WEEBLY_ACCESS_TOKEN_URL"), [
            "form_params" => [
                'client_id' => $sourceConnector->clientId,
                'client_secret' => $sourceConnector->clientSecret,
                'authorization_code' => $authorizationCode
            ]
        ]);
    }

    public function getResponseBody (GuzzleHttpResponse $response) : \stdClass {
        return (object) json_decode((string) $response->getBody());
    }

    public function decodeUserInfo (string $jwtToken, Connector $sourceConnector) : array {
        return (array) JWT::decode($jwtToken, $sourceConnector->clientSecret, [ env("JWT_APP_SIGNATURE") ]);
    }

    public function createProduct(GuzzleClient $client, \stdClass $weeblyProductData, string $redisKey, RedisClient $redisClient) {
        $redisRes = $redisClient->find($redisKey);
        $siteId = $redisRes['site_id'];
        $token = $redisRes['weebly_access_token'];
        $apiUrl = self::$apiHost. "/v1/user/sites/" . $siteId . "/store/products";

        $result = (object)[ "status" => false ];

        $headerParams = [
            'Accept' => self::WEEBLY_ACCEPT_HEADER,
            'Content-Type' => self::WEEBLY_CONTENT_TYPE_HEADER,
            'X-Weebly-Access-Token' => $token
        ];

        try{
            $response = $client->request('POST', $apiUrl, [
                'headers' => $headerParams,
                'body' => json_encode($weeblyProductData)
            ]);

            $res = self::getResponseBody($response);

            $result->weebly_product_id = $res->product_id;
            $result->source_product_id = $weeblyProductData->skus;
            $result->status = true;

        } catch (Exception $e) {
            $result->message = "Product not created.";
        }

        return $result;
    }

    public function getWeeblyProductIDFromSourceMapping (Source $source, string $productId) : ?string {
        $mapping = $source->productMappings()->where("source_product_id", $productId)->first();
        if ($mapping) {
            return $mapping->weebly_product_id;
        }
    }

    public function updateProduct (GuzzleClient $guzzleClient, WeeblyProductUpdateService $weeblyProductUpdate, Site $site, int $productId) : GuzzleHttpResponse {
        $endpoint = self::$apiHost . "/v1/user/sites/{$site->weebly_site_id}/store/products/{$productId}";
        return $guzzleClient->request("PATCH", $endpoint, [
            'headers' => [
                'Accept' => self::WEEBLY_ACCEPT_HEADER,
                'Content-Type' => self::WEEBLY_CONTENT_TYPE_HEADER,
                'X-Weebly-Access-Token' => $site->oauth_token
            ],
            'body' => json_encode($weeblyProductUpdate)
        ]);
    }

    public function deleteProduct (GuzzleClient $guzzleClient, Site $site, string $productId) : bool {
        $endpoint = self::$apiHost . "/v1/user/sites/{$site->weebly_site_id}/store/products/{$productId}";
        $response = $guzzleClient->request("DELETE", $endpoint, [
            'headers' => [
                'Accept' => self::WEEBLY_ACCEPT_HEADER,
                'Content-Type' => self::WEEBLY_CONTENT_TYPE_HEADER,
                'X-Weebly-Access-Token' => $site->oauth_token
            ]
        ]);
        return $response->getStatusCode() == "204"; // 204 -> somethings was deleted
    }

    public function getProductCount(GuzzleClient $client, string $redisKey, RedisClient $redisClient) {
        $redisRes = $redisClient->find($redisKey);
        $siteId = $redisRes['site_id'];
        $token = $redisRes['weebly_access_token'];
        $apiUrl = self::$apiHost. "/v1/user/sites/" . $siteId . "/store/products/count";

        $headerParams = [
            'Accept' => self::WEEBLY_ACCEPT_HEADER,
            'Content-Type' => self::WEEBLY_CONTENT_TYPE_HEADER,
            'X-Weebly-Access-Token' => $token
        ];

        $response = $client->request('GET', $apiUrl, ['headers' => $headerParams]);
        $data = self::getResponseBody($response);

        return (int) $data->count;
    }

    /**
     * Update a dashboard card
     * @param  GuzzleClient $guzzleClient    Guzzle client to make requests with
     * @param  Site         $site            user's site id
     * @param  string       $dashboardCardId id of dashboard card to be updated
     * @param  array        $cardUpdate      update for the dashboard card
     * @return GuzzleHttpResponse            response from Weebly after update
     */
    public function updateDashboardCard (GuzzleClient $guzzleClient, Site $site, string $dashboardCardId, array $cardUpdate) : GuzzleHttpResponse {
        $endpoint = self::$apiHost."/v1/user/sites/{$site->weebly_site_id}/cards/{$dashboardCardId}";
dd($endpoint);
        return $guzzleClient->request("PATCH", $endpoint, [
            "headers" => [
                "Accept" => self::WEEBLY_ACCEPT_HEADER,
                "Content-Type" => self::WEEBLY_CONTENT_TYPE_HEADER,
                "X-Weebly-Access-Token" => $site->oauth_token,
            ],

            "body" => json_encode([
                "hidden" => false,
                "card_data" => $cardUpdate
            ])
        ]);
    }
}