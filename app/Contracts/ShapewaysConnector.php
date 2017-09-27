<?php 

namespace App\Contracts;

use App\Source;
use App\Contracts\Connector;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response as GuzzleHttpResponse;

class ShapewaysConnector extends Connector {

    /**
     * @var The name of the connector.
     */
    public $name = "shapeways";
    public $productFieldName = "models";
    public $productIdFieldName = "modelId";
    public $modelsURI;

    /**
     * @var Host url for the connector.
     */
    public $apiHost;

    public function __construct (string $id, string $secret, string $cardId) {
        $this->apiHost = env("SHAPEWAYS_API_HOST");
        $this->modelsURI = $this->apiHost . "/models/v1";
        parent::__construct($id, $secret, $cardId);
    }

    /**
     * getAuthorizationUrl - authorization url for shapeways.
     * @return string url
     */
    public function getAuthorizationUrl() : string {
        return $this->apiHost . "/oauth2/authorize?response_type=code&client_id=" . env("SHAPEWAYS_CLIENT_ID") . "&redirect_uri=" . route("source.connect.callback", ["source_connector" => "shapeways"]);
    }

    /**
     * getAccessToken - Get accessToken from weebly.
     * @param $authCode The required authorization code required.
     * @param $guzzleClient GuzzleClient to make the request.
     * @return stdClass object - The access token.
     */
    public function getAccessToken (string $authCode, GuzzleClient $guzzleClient) : GuzzleHttpResponse {
        return $guzzleClient->request("POST", "https://api.shapeways.com/oauth2/token", [
            'form_params' => [
                'client_id' => env("SHAPEWAYS_CLIENT_ID"),
                'client_secret' => env("SHAPEWAYS_CLIENT_SECRET"),
                'grant_type' => "client_credentials",
                "code" => $authCode,
            ]
        ]);
    }

    /**
     * getProducts - retrieves Models from shapeways
     * @param $guzzleClient GuzzleClient to make the request.
     * @param $token The required authorization code required.
     *
     * @return stdClass object - All user model products from shapeways.
    */
    public function getProducts (GuzzleClient $guzzleClient, string $token) : GuzzleHttpResponse {
        return $guzzleClient->request("GET", $this->modelsURI, [
            'headers' => [
                'Authorization' => "Bearer $token"
            ]
        ]);
    }
  
    /**
     * getFullProducts - retrieves products from Models in shapeways
     * @param $guzzleClient GuzzleClient to make the request.
     * @param $modelId
     * @param $token
     *
     * @return stdClass object - List of products associated with the model supplied.
     */
    public function getFullProducts(GuzzleClient $guzzleClient, string $modelId, string $token) : GuzzleHttpResponse {
        $endpoint = $this->apiHost . "/models/" . $modelId . "/v1";
        return $guzzleClient->request("GET", $endpoint, [
            'headers' => [
                'Authorization' => "Bearer $token"
            ]
        ]);
    }

    public function mapProduct (\stdClass $product) : \stdClass {
        return (object) [
            "name" => $product->title,
            'skus' => $product->modelId,
            'short_description​' => $product->description,
            'images' => $product->fileName,
            'options' => $product->urls->publicProductUrl
        ];
    }

    public function dashboardWelcomeCard (string $hostURL) : array {
        return []; // @TODO - Shapeways welcome card update
    }

    public function dashboardSyncedCard (int $numberOfProductsSynced, string $hostURL) : array {
        return []; // @TODO - Shapeways synced card update
    }

    public function activateSource (Source $source, array $credentials) : bool {
        return true; // @TODO activate source for shapeways
    }

    public function deactivateSource (Source $source) : bool {
        return false; // @TODO deactivate source for shapeways
    }
}