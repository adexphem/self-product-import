<?php

namespace Tests\Unit\Connectors;

use App\Site;
use App\Services\WeeblyProductUpdateService;
use Mockery as M;
use Tests\TestCase;
use App\Contracts\MindbodyConnector;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MindbodyConnectorTest extends TestCase
{
	private $connector;
	private $expectedResponse;

	public function setUp () {
		$this->connector = new MindbodyConnector("MINDBODY_TEST_ID", "MINDBODY_TEST_SECRET", "MINDBODY_TEST_DASHBOARD_CARD_ID");
		$this->expectedResponse = (object) [
			"ErrorCode" => 200,
			"testMethodNameResult" => []
        ];
        parent::setUp();
        $this->site = factory(Site::class)->create();
	}

    public function testConnectorCanMapProductsToWeebly () {
        $product = $this->getMindbodyTestProduct();

        $mappedWeeblyProduct = $this->connector->mapProduct($product);
        $this->assertInstanceof(\stdClass::class, $mappedWeeblyProduct);
        $this->assertEquals($mappedWeeblyProduct->name, $product->Name);
        $this->assertEquals($mappedWeeblyProduct->short_description, $product->ShortDesc);

        $productSKU = ($mappedWeeblyProduct->skus)[0];
        $this->assertEquals($productSKU["price"], $product->Price);
        $this->assertEquals($productSKU["product_type"], "physical");
        $this->assertEquals($productSKU["sale_price"], "120.0000");
    }

    public function testConnectorCanMapProductForUpdate () {
        $product = $this->getMindbodyTestProduct();
        $update = $this->connector->mapProductForUpdate(
            $product,
            $this->site,
            45
        );

        $this->assertInstanceof(WeeblyProductUpdateService::class, $update);

        $this->assertEquals($this->site->weebly_site_id, $update->site_id);
        $this->assertEquals(45, $update->site_product_id);
        $this->assertEquals($product->Name, $update->name);
        $this->assertEquals($product->ShortDesc, $update->short_description);
        $this->assertEquals(false, $update->published);
        $this->assertEquals(false, $update->taxable);
    }


	public function testConnectorCanMakeSoapCallWithSuccess () {
		$soapRequestParams = [];
		$soapClientMock = M::mock(\SoapClient::class);

		$soapClientMock->shouldReceive('__soapCall')->with("testMethodName", [
			"testMethodName" => $soapRequestParams
		])->andReturn($this->expectedResponse);

		$response = $this->connector->makeSoapCall("testServiceName", "testMethodName", $soapRequestParams, $soapClientMock);

		$this->assertEquals($response->ErrorCode, 200);
		$this->assertEquals($response->testMethodNameResult, []);
	}

	public function testConnectorCanGetSuccessDataFromSoapResponse () {
		$data = $this->connector->getDataFromResponse($this->expectedResponse);

		$this->assertEquals($this->expectedResponse, $data);
	}

	public function testConnectorWillReturnNullIfRequestFails () {
		$data = $this->connector->getDataFromResponse( (object) [
			"ErrorCode" => 400
		]);

		$this->assertNull($data);
    }

    public function testConnectorUsesMindbodyRequestsTrait () {
    	$traitMethods = class_uses($this->connector);

    	$this->assertContains("App\Contracts\MindbodyRequests", array_keys($traitMethods));
    	$this->assertContains("App\Contracts\MindbodyRequests", array_values($traitMethods));
    }

    public function testConnectorUsesGetProductsRequestParameters () {
    	$siteIDs = [ 111 ];
    	$GetProductsParams = [
    		"Request" => [
    			"SourceCredentials" => [
    				"SourceName" => "__MINDBODY_TEST_SOURCENAME__",
    				"Password" => "__MINDBODY_TEST_PASSWORD__",
    				"SiteIDs" => $siteIDs
    			],
    			"XMLDetail" => "Full",
    			"SellOnline" => true
    		]
    	];

    	$requestParams = $this->connector->GetProductsRequestParameters($siteIDs);
    	$this->assertEquals($GetProductsParams, $requestParams);
    }

    public function testConnectorUsesGetActivationCodeParameters () {
    	$siteIDs = [ 111 ];
    	$GetActivationCodeParams = [
    		"Request" => [
    			"SourceCredentials" => [
    				"SourceName" => "__MINDBODY_TEST_SOURCENAME__",
    				"Password" => "__MINDBODY_TEST_PASSWORD__",
    				"SiteIDs" => $siteIDs
    			]
    		]
    	];

    	$requestParams = $this->connector->GetActivationCodeParameters($siteIDs);
    	$this->assertEquals($GetActivationCodeParams, $requestParams);
    }

    public function testconnectorCanGetStudioIdFromSource () {
    	$source = factory(\App\Source::class)->create();
    	$studioId = $this->connector->getStudioIdFromSource($source);

    	$this->assertEquals("-99999", $studioId);
    }

    public function testConnectorWelcomeDashboardCardUpdate () {
    	$update = $this->connector->dashboardWelcomeCard("__TEST_CONNECT_URL__");
    	$expectedUpdate = [
            [
                "type" => "welcome",
                "headline" => "Welcome to MINDBODY!",
                "text" => "Connect your Mindbody store to import your products and start selling on your Weebly website",
                "action_label" => "Connect My Site",
                "action_link" => "__TEST_CONNECT_URL__/mindbody/connect" // use the baseURL
            ]
        ];
        $this->assertEquals($expectedUpdate, $update);
    }

    public function testConnectorSyncedProductsNumberDashboardCardUpdate () {
    	$update = $this->connector->dashboardSyncedCard(50, "__TEST_CONNECT_URL__");
    	$expectedUpdate = [
            [
                [
                    "type" => "stat",
                    "value" => "Number of products Synced",
                    "primary_value" => 50,
                    "primary_label" => "Products synced."
                ],
                [
                    "type" => "action",
                    "label" => "Sync Products Now",
                    "link" => "__TEST_CONNECT_URL__/mindbody/sync"
                ]
            ]
        ];
        $this->assertEquals($expectedUpdate, $update);
    }
}
