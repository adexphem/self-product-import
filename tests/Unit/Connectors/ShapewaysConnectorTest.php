<?php

namespace Tests\Unit\Connectors;

use Mockery as M;
use Tests\TestCase;
use App\Contracts\ShapewaysConnector;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response as GuzzleHttpResponse;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ShapewaysConnectorTest extends TestCase
{
	private $connector;
	private $guzzleClientMock;
	private $guzzleResponseMock;

	public function setUp () {
		$this->connector = new ShapewaysConnector("SHAPEWAYS_TEST_ID", "SHAPEWAYS_TEST_SECRET", "SHAPEWAYS_TEST_DASHBOARD_CARD_ID");
		$this->guzzleClientMock = M::mock(GuzzleClient::class);
		$this->guzzleResponseMock = M::mock(GuzzleHttpResponse::class);
		parent::setUp();
	}

	public function testShapewaysConnectorCanGetAuthorizationURL () {
		$url = $this->connector->getAuthorizationUrl();
		$expectedURL = "https://api.shapeways-test.dev/oauth2/authorize?response_type=code&client_id=SHAPEWAYS_TEST_CLIENT_ID&redirect_uri=http://localhost/shapeways/callback";
		$this->assertEquals($expectedURL, $url);
	}

	public function testShapewaysConnectorCanGetAccessToken () {
		$this->guzzleClientMock->shouldReceive('request')->with("POST", "https://api.shapeways.com/oauth2/token", [
			'form_params' => [
				"client_id" => "SHAPEWAYS_TEST_CLIENT_ID",
				"client_secret" => "SHAPEWAYS_TEST_CLIENT_SECRET",
				"grant_type" => "client_credentials",
				"code" => "__TEST_SHAPEWAYS_AUTH_CODE__"
			]
		])->andReturn($this->guzzleResponseMock);

		$response = $this->connector->getAccessToken("__TEST_SHAPEWAYS_AUTH_CODE__", $this->guzzleClientMock);
		$this->assertEquals($this->guzzleResponseMock, $response);
	}
	
	public function testConnectorCanGetProducts () {
		$this->guzzleClientMock->shouldReceive('request')->with("GET", "https://api.shapeways-test.dev/models/v1", [
			'headers' => [
				'Authorization' => "Bearer __TEST_SHAPEWAYS_ACCESS_TOKEN__"
			]
		])->andReturn($this->guzzleResponseMock);

		$response = $this->connector->getProducts($this->guzzleClientMock, "__TEST_SHAPEWAYS_ACCESS_TOKEN__");
		$this->assertEquals($this->guzzleResponseMock, $response);
	}

	public function testConnectorCanGetProductFullDetails () {
		$this->guzzleClientMock->shouldReceive('request')->with("GET", "https://api.shapeways-test.dev/models/567891/v1", [
			'headers' => [
				'Authorization' => "Bearer __TEST_SHAPEWAYS_ACCESS_TOKEN__"
			]
		])->andReturn($this->guzzleResponseMock);

		$response = $this->connector->getFullProducts($this->guzzleClientMock, "567891", "__TEST_SHAPEWAYS_ACCESS_TOKEN__");
		$this->assertEquals($this->guzzleResponseMock, $response);
	}

	public function testConnectorCanMapProductsToWeebly () {
		$product = (object) [
			"title" => "Shapeways Test Product",
			"modelId" => "123456789",
			"description" => "Short description about the shapeways test product",
			"fileName" => "url to the images",
			"urls" => (object) [
				"publicProductUrl" => "product public url"
			]
		];

		$mappedWeeblyProduct = $this->connector->mapProduct($product);

		$this->assertEquals($mappedWeeblyProduct->name, $product->title);
		$this->assertEquals($mappedWeeblyProduct->skus, $product->modelId);
		$this->assertEquals($mappedWeeblyProduct->images, $product->fileName);
		$this->assertEquals($mappedWeeblyProduct->short_description​, $product->description);
		$this->assertEquals($mappedWeeblyProduct->options, $product->urls->publicProductUrl);
		$this->assertInstanceof(\stdClass::class, $mappedWeeblyProduct);
	}
}
