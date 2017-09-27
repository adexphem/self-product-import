<?php

namespace Tests;

use Artisan;
use App\Services\WeeblyProductUpdateService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected $baseURL = "http://localhost:5000";

    public function setUp () {
    	parent::setUp();
    	Artisan::call('migrate');
    }

    public function tearDown () {
    	Artisan::call('migrate:reset');
    	parent::tearDown();
    }

    public function getMindbodyTestProduct () : \stdClass {
        return (object) [
            "Price" => "120.0000",
            "TaxRate" => "0.13",
            "ID" => "00015655",
            "GroupID" => 15655,
            "Name" => "Mindbody Test Product",
            "OnlinePrice" => "120.0000",
            "ShortDesc" => "Short description about the mindbody test product",
            "LongDesc" => "This is a longer description about the mindbody test product. This is meant to be much longer.",
            "Color" => (object) [
                "ID" => 100,
                "Name" => "None"
            ],
            "Size" => (object) [
                "ID" => 100,
                "Name" => "None"
            ]
        ];
    }

    public function getMindbodyTestProductUpdate (string $testProductId) : WeeblyProductUpdateService {
        return new WeeblyProductUpdateService (
            "1234567890",
            $testProductId,
            "Mindbody Test Product",
            "Short description about the mindbody test product"
        );
    }

    public function testAllRequiredENVVariablesAreProvided () {
        $keys = array_keys($_ENV);
        $requiredKeys = [
            "JWT_APP_SIGNATURE", // signature to decode jwt token

            "WEEBLY_OAUTH_CALLBACK_URL", // oauth callback url for Weebly authentication
            "WEEBLY_ACCESS_TOKEN_URL", // url for weebly access token

            "SHAPEWAYS_API_HOST", // shapeways api host
            "SHAPEWAYS_CLIENT_ID", // shapeways client id for oauth2
            "SHAPEWAYS_CLIENT_SECRET", // shapeways client secret for oauth2

            "MINDBODY_API_HOST", // mindbody soap api host
            "MINDBODY_APP_SOURCE_NAME", // mindbody api source name
            "MINDBODY_APP_PASSWORD", // mindbody api password

            "WEEBLY_MINDBODY_CLIENT_ID", // Mindbody client id for dashboard card installation
            "WEEBLY_MINDBODY_CLIENT_SECRET", // Mindbody client secret for dashboard card installation
            "WEEBLY_MINDBODY_DASHBOARD_CARD_ID", // Mindbody dashboard card id - used to communicate with the product sync app

            "WEEBLY_SHAPEWAYS_CLIENT_ID", // Shapeways client id for dashboard card installation
            "WEEBLY_SHAPEWAYS_CLIENT_SECRET", // Shapeways client secret for dashboard card installation
            "WEEBLY_SHAPEWAYS_DASHBOARD_CARD_ID", // Shapeways dashboard card id - used to communicate with the product sync app
        ];
        collect($requiredKeys)->map(function ($key) use ($keys) {
            $this->assertContains($key, $keys);
        });
    }

    public function testStaticEnvVariables () {
        $this->assertEquals("HS256", $_ENV["JWT_APP_SIGNATURE"]);
        $this->assertEquals("https://www.weebly.com/app-center/oauth/authorize", $_ENV["WEEBLY_OAUTH_CALLBACK_URL"]);
        $this->assertEquals("https://www.weebly.com/app-center/oauth/access_token", $_ENV["WEEBLY_ACCESS_TOKEN_URL"]);
    }
}
