<?php

namespace Tests\Feature;

use App\User;
use App\Site;
use App\Source;
use Mockery as M;
use Tests\TestCase;
use Tests\Mocks\WeeblyMock;
use App\Contracts\Connector;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Request;
use App\Helpers\SyncActivityLog;
use Illuminate\Support\Facades\Route;
use App\Repositories\WeeblyRepository;
use App\Repositories\SourcesRepository;
use App\Services\WeeblyProductUpdateService;
use GuzzleHttp\Psr7\Response as GuzzleHttpResponse;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SourcesControllerTest extends TestCase
{
    private $weeblyDataMock;
    private $testUser;
    private $testSite;
    private $testMindbodyConnectedSource;

    private $cardId;
    private $connectorMock;
    private $weeblyRepoMock;

    public function setUp () {
        $this->testReferer = "__TEST_REFERER__";
        parent::setUp();
    	
    	$this->testUser = factory(User::class)->create([
    		"weebly_site_id" => WeeblyMock::WEEBLY_MOCK_SITE_ID,
    		"weebly_user_id" => WeeblyMock::WEEBLY_MOCK_USER_ID
    	]);
    	
    	$this->testSite = factory(Site::class)->create([
    		"weebly_site_id" => WeeblyMock::WEEBLY_MOCK_SITE_ID,
    		"weebly_user_id" => WeeblyMock::WEEBLY_MOCK_USER_ID
    	]);

    	$this->testMindbodyConnectedSource = factory(Source::class)->create([
    		"site_id" => WeeblyMock::WEEBLY_MOCK_SITE_ID,
    		"type" => "mindbody",
    		"credentials" => json_encode([
    			"studio_id" => "-99999"
    		]),
    		"activation_status" => "connected"
    	]);

        $this->cardId = "__TEST_CARD_ID__";
        $this->connectorMock = M::mock(Connector::class);
        $this->weeblyRepoMock = M::mock(WeeblyRepository::class);
        $this->loggerMock = M::mock(SyncActivityLog::class);

    	$this->weeblyDataMock = new WeeblyMock ();
    }

    public function testMindbodyProductSyncCreatesAllNewProductsAndUpdatesExistingProducts () {
        $responseFromMindbody = $this->weeblyDataMock->mindbodyMockGetProductsResponse();
        $products = $responseFromMindbody->GetProductsResult->Products->Product;
        collect($products)
            ->random(4) // randomly select 4 products to save in ProductMapping Table
            ->each(function ($product, $count) {
                // create a mapping for each product
                return $this->testMindbodyConnectedSource->productMappings()->create([
                    'source_product_id' => $product->ID,
                    'weebly_product_id' => $count
                ]);
            });

        $this->assertCount(4, $this->testMindbodyConnectedSource->productMappings);

        $this->connectorMock->name = "mindbody";
        $this->connectorMock->cardId = $this->cardId;

        $this->weeblyRepoMock
            ->shouldReceive("decodeUserInfo")
            ->with(WeeblyMock::WEEBLY_MOCK_JWT_TOKEN, $this->connectorMock)
            ->andReturn([
                "iat" => 1505020402,
                "user_id" => WeeblyMock::WEEBLY_MOCK_USER_ID,
                "site_id" => WeeblyMock::WEEBLY_MOCK_SITE_ID,
                "language" => "en"
            ]);

        $studioId = "-99999";

        $productCreateResult = (object) [
            "status" => true,
            "weebly_product_id" => 45, // to refactor
        ];

        $this->connectorMock
            ->shouldReceive("getStudioIdFromSource")
            ->andReturn($studioId)
            ->shouldReceive("getProducts")
            ->with([ $studioId ])
            ->andReturn($responseFromMindbody)
            ->shouldReceive('getDataFromResponse')
            ->with($responseFromMindbody)
            ->andReturn($responseFromMindbody->GetProductsResult)
            ->shouldReceive('mapProduct')
            ->times(12) // assert all 12 products should be mapped to weebly format
            ;

        $this->weeblyRepoMock
            ->shouldReceive('createProduct')
            ->times(8)
            ->andReturn($productCreateResult); // assert only 8 products are created in Weebly endpoint, because 4 have already been created

        $weeblyProductUpdateMock = M::mock(WeeblyProductUpdateService::class);
        $guzzleResponseMock = M::mock(GuzzleHttpResponse::class);

        $this->weeblyRepoMock
            ->shouldReceive('getWeeblyProductIDFromSourceMapping')
            ->times(4)
            ->andReturn("45");

        $this->connectorMock
            ->shouldReceive('mapProductForUpdate')
            ->times(4)
            ->andReturn($weeblyProductUpdateMock)
            ;

        $this->weeblyRepoMock
            ->shouldReceive("updateProduct")
            ->times(4)
            ->andReturn($guzzleResponseMock)
            ->shouldReceive('getResponseBody')
            ->with($guzzleResponseMock)
            ->andReturn((object) [])
            ;

        $dashboardCardUpdate = [];// array representing dashboard card update.

        $this->connectorMock
            ->shouldReceive("dashboardSyncedCard")
            ->with(12, url("/")) // assert all 12 products have been synced.
            ->andReturn($dashboardCardUpdate);

        $this->weeblyRepoMock->shouldReceive("updateDashboardCard");

        Route::bind('source_connector', function () {
            return $this->connectorMock;
        });
        $this->instance(WeeblyRepository::class, $this->weeblyRepoMock);

        $response = $this->get(route("source.sync", [
            "source_connector" => 'mindbody',
            'jwt' => WeeblyMock::WEEBLY_MOCK_JWT_TOKEN
        ]), [
            "referer" => $this->testReferer
        ]);

        $responseContent = json_decode($response->getContent());
        $this->assertEquals(12, $responseContent->productsSyncedCount);

        $response->assertStatus(200);
    }

    public function testMindbodyProductSyncCreatesAllProducts () {
        $cardId = "__TEST_CARD_ID__";
        $this->connectorMock->name = "mindbody";
        $this->connectorMock->cardId = $this->cardId;

        $this->weeblyRepoMock
            ->shouldReceive("decodeUserInfo")
            ->with(WeeblyMock::WEEBLY_MOCK_JWT_TOKEN, $this->connectorMock)
            ->andReturn([
                "iat" => 1505020402,
                "user_id" => WeeblyMock::WEEBLY_MOCK_USER_ID,
                "site_id" => WeeblyMock::WEEBLY_MOCK_SITE_ID,
                "language" => "en"
            ]);

        $studioId = "-99999";

        $responseFromMindbody = $this->weeblyDataMock->mindbodyMockGetProductsResponse();

        $productCreateResult = (object) [
            "status" => true,
            "weebly_product_id" => 45, // to refactor
        ];

        $this->connectorMock
            ->shouldReceive("getStudioIdFromSource")
            ->andReturn($studioId)
    		->shouldReceive("getProducts")
    		->with([ $studioId ])
    		->andReturn($responseFromMindbody)
            ->shouldReceive('getDataFromResponse')
            ->with($responseFromMindbody)
            ->andReturn($responseFromMindbody->GetProductsResult);

        $mockLogData = []; // using an empty array just for testing

        $this->loggerMock
            ->shouldReceive("formatLogData")
            ->andReturn($mockLogData)
            ->shouldReceive('log');

        $this->connectorMock
            ->shouldReceive('mapProduct')
            ->times(12);

        $this->weeblyRepoMock
            ->shouldReceive('createProduct')
            ->times(12)
            ->andReturn($productCreateResult); // assert 12 products are created in Weebly endpoint

        $dashboardCardUpdate = []; // array representing dashboard card update.

        $this->connectorMock
            ->shouldReceive("dashboardSyncedCard")
            ->with(12, url("/")) // because 12 is the number of products that were synced. - refactor
            ->andReturn($dashboardCardUpdate);

        $this->weeblyRepoMock->shouldReceive("updateDashboardCard"); // assert that it called the method to update the dashboard card
        
        Route::bind('source_connector', function () {
            return $this->connectorMock;
        });
        $this->instance(SyncActivityLog::class, $this->loggerMock);
        $this->instance(WeeblyRepository::class, $this->weeblyRepoMock);
        
    	$response = $this->get(route("source.sync", [
    		"source_connector" => 'mindbody',
    		'jwt' => WeeblyMock::WEEBLY_MOCK_JWT_TOKEN
    	]));

        $response->assertStatus(200);
    }
}
