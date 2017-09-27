<?php 

namespace App\Contracts;

use App\Source;
use App\Site;
use SoapClient;
use App\Repositories\SourcesRepository;
use App\Contracts\Connector;
use App\Services\WeeblyProductUpdateService;

class MindbodyConnector extends Connector {
	use MindbodyRequests;

	public $name = "mindbody";
	public $apiHost;

	public function __construct (string $id, string $secret, string $cardId) {
		$this->apiHost = env("MINDBODY_API_HOST");
		parent::__construct($id, $secret, $cardId);
	}

	public function getDataFromResponse (\stdClass $MindbodyResponseBody) : ?\stdClass {
		return ($MindbodyResponseBody->ErrorCode === 200) ? $MindbodyResponseBody : null;
	}

	public function getProducts (array $siteIDs) : ?\stdClass {
		$response = $this->makeSoapCall("SaleService", "GetProducts", $this->GetProductsRequestParameters($siteIDs));
		return $this->getDataFromResponse($response->GetProductsResult);
	}

	public function mapProduct (\stdClass $product) : \stdClass {
		
		return (object) [
			'name' => $product->Name,
			'skus' => [
				[
					'price' => $product->Price,
					'product_type' => 'physical',
					'sale_price' => ($product->OnlinePrice ?? $product->Price ?? 0.00),
					'current_price' => ($product->OnlinePrice ?? $product->Price ?? 0.00)
				]
			],
			'short_description' => $product->ShortDesc ?? $product->LongDesc ?? ""
		];
	}

	public function mapProductForUpdate (\stdClass $product, Site $site, string $productId, bool $published = false, bool $taxable = false) : WeeblyProductUpdateService {
		return new WeeblyProductUpdateService (
			$site->weebly_site_id,
			$productId,
			$product->Name,
			$product->ShortDesc ?? $product->LongDesc ?? "",
			$published,
			$taxable
		);
	}

	public function getActivationCode (array $siteIDs) {
		$response = $this->makeSoapCall("SiteService", "GetActivationCode", $this->GetActivationCodeParameters($siteIDs));
		return $this->getDataFromResponse($response->GetActivationCodeResult);
	}

	public function makeSoapCall (string $serviceName, string $methodName, array $requestParams, SoapClient $client = null) {
		$client = is_null($client) ? $this->createMindbodySoapClient($serviceName) : $client;

		return $client->__soapCall($methodName, [
			"$methodName" => $requestParams
		]);
	}

	public function createMindbodySoapClient (string $serviceName) : SoapClient {
		return new SoapClient($this->apiHost . "/0_5/" . $serviceName . ".asmx?wsdl");
	}

	public function getStudioIdFromSource (Source $source) : ? string {
		return is_null($source->credentials) ? null : json_decode($source->credentials)->studio_id;
	}

	public function activateSource (Source $source, array $credentials) : bool {
		return $source->update([
			'activation_status' => SourcesRepository::CONNECTED_SOURCE,
			'credentials' => json_encode($credentials)
		]);
	}

	public function deactivateSource (Source $source) : bool {
		return $source->update([
			'activation_status' => SourcesRepository::DISCONNECTED_SOURCE,
			'credentials' => null
		]);
	}

	public function makeCredentials (string $studioId) : array {
		return [ "studio_id" => $studioId ];
	}

	public function dashboardWelcomeCard (string $hostURL) : array {
		return [
			[
				"type" => "welcome",
				"headline" => "Welcome to MINDBODY!",
				"text" => "Connect your MINDBODY store to import your products and start selling on your Weebly website",
				"action_label" => "Connect My Site",
				"action_link" => $hostURL . "/$this->name" . "/connect"
			]
		];
	}

	public function dashboardSyncedCard (int $numberOfProductsSynced, string $hostURL) : array {
		return [
			[
				[
					"type" => "stat",
					"value" => "Number of products Imported",
					"primary_value" => $numberOfProductsSynced,
					"primary_label" => "Products Imported."
				],
				[
					"type" => "action",
					"label" => "Import Products Now",
					"link" => $hostURL . "/$this->name" . "/connect"
				]
			]
		];
	}

    public function getDashboardCardId() : string {
        return env("WEEBLY_MINDBODY_DASHBOARD_CARD_ID");
	}

}