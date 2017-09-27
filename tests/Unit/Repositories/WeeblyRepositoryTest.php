<?php

namespace Tests\Unit\Repositories;

use App\Site;
use Mockery as M;
use App\Repositories\WeeblyProductUpdate;
use App\Contracts\Connector, App\Contracts\MindbodyConnector;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Response as GuzzleHttpResponse;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use App\Repositories\WeeblyRepository;

class WeeblyRepositoryTest extends TestCase
{
    private $guzzleClientMock;
    private $guzzleResponseMock;
    private $site;
    private $connectorMock;
    private $weeblyRepository;

    public function setUp () {
        $this->guzzleClientMock = M::mock(GuzzleClient::class);
        $this->guzzleResponseMock = M::mock(GuzzleHttpResponse::class);
        $this->connectorMock = M::mock(Connector::class);

        $this->connectorMock->name = "testsource";
        $this->connectorMock->clientId = env("WEEBLY_MINDBODY_CLIENT_ID");
        $this->connectorMock->clientSecret = env("WEEBLY_MINDBODY_CLIENT_SECRET");
        
        parent::setUp();
        
        $this->weeblyRepository = new WeeblyRepository();
        $this->site = factory(Site::class)->create();
    }

    public function testRepositoryWillAllowValidHMACHash () {
    	$validHash = "10786f83c18b6196fb23546e69848359fd059daa255f75bf8f539695d08936bf";
    	$isValid = $this->weeblyRepository->validateHMAC("user_id=100&site_id=200", $validHash, $this->connectorMock);
    	$this->assertTrue($isValid);
    }

    public function testRepositoryWillNotAllowInvalidHMACHash () {
    	$invalidHash = "invalid";
    	$isValid = $this->weeblyRepository->validateHMAC("user_id=100&site_id=200", $invalidHash, $this->connectorMock);
    	$this->assertNotTrue($isValid);
    }

    public function testRepositoryCanPrepareParameters () {
    	$params = $this->weeblyRepository->prepareParams([
    		'user_id' => "100",
            'timestamp' => "9999",
            'site_id' => "8888",
    	]);
    	$this->assertEquals([
    		'user_id' => "100",
            'timestamp' => "9999",
            'site_id' => "8888",
    	], $params);
    }

    public function testRepositoryCanIgnoreMissingParams () {
    	$params = $this->weeblyRepository->prepareParams([
    		'user_id' => "100",
            'timestamp' => "9999",
            'site_id' => "",
    	]);
    	$this->assertEquals([
    		'user_id' => "100",
            'timestamp' => "9999",
    	], $params);
    }

    public function testRepositoryCanGetCallbackQuery () {
        $callbackURL = $this->weeblyRepository->getCallbackQuery([
            'user_id' => "100",
            'timestamp' => "9999",
            'site_id' => "8888",
            'version' => "1.0.0",
            'callback_url' => 'https://www.weebly.com/app-center/oauth/authorize'
        ], $this->connectorMock);

        $expectedCallbackURL = "https://www.weebly.com/app-center/oauth/authorize?client_id=APP_TEST_ID&user_id=100&timestamp=9999&site_id=8888&version=1.0.0&scope=read%3Astore-catalog%2Cwrite%3Astore-catalog&redirect_uri=http%3A%2F%2Flocalhost%2Fauth%2Ftestsource%2Fphasetwo";
        $this->assertEquals($expectedCallbackURL, $callbackURL);
    }

    public function testRepoCanGetAcessToken () {
        $this->guzzleClientMock
            ->shouldReceive('request')
            ->withArgs(["POST", "https://www.weebly.com/app-center/oauth/access_token", [
                "form_params" => [
                    'client_id' => env("WEEBLY_MINDBODY_CLIENT_ID"),
                    'client_secret' => env("WEEBLY_MINDBODY_CLIENT_SECRET"),
                    'authorization_code' => "TEST_AUTH_CODE"
                ]
            ]])
            ->andReturn($this->guzzleResponseMock);

        $response = $this->weeblyRepository->getAcessToken($this->guzzleClientMock, "TEST_AUTH_CODE", $this->connectorMock);
        $this->assertEquals($this->guzzleResponseMock, $response);
    }

    public function testRepositoryCanDecodeJWTToken () {
        $JWTMock = M::mock('alias:Firebase\JWT\JWT');
        $jwtResponse = [
            "user_id" => "111111111",
            "site_id" => "999999999",
            "language" => "en",
            "iat" => 1500279143
        ];
        
        $JWTMock->shouldReceive('decode')
            ->with("__APP_JWT_TEST_TOKEN__", env("WEEBLY_MINDBODY_CLIENT_SECRET"), ["HS256"])
            ->andReturn($jwtResponse);

        $decoded = $this->weeblyRepository->decodeUserInfo("__APP_JWT_TEST_TOKEN__", $this->connectorMock);
        $this->assertEquals($jwtResponse, $decoded);
    }

    public function testRepositoryCanGetBodyFromGuzzleResponse () {
        $stream = Psr7\stream_for('{"data" : "This is just a mock 200 response from Guzzle"}');
        $guzzleResponse = new GuzzleHttpResponse(200, ['Content-Type' => 'application/json'], $stream);

        $responseBody = $this->weeblyRepository->getResponseBody($guzzleResponse);

        $this->assertEquals("This is just a mock 200 response from Guzzle", $responseBody->data);
    }

    public function testRepositoryCanUpdateAProductOnWeebly () {
        $testProductId = "45";
        $updateData = $this->getMindbodyTestProductUpdate($testProductId);

        $this->guzzleClientMock
            ->shouldReceive('request')
            ->withArgs(["PATCH", "https://api.weebly.com/v1/user/sites/{$this->site->weebly_site_id}/store/products/{$testProductId}", [
                "headers" => [
                    "Accept" => "application/vnd.weebly.v1+json",
                    "Content-Type" => "application/json",
                    "X-Weebly-Access-Token" => $this->site->oauth_token
                ],
                'body' => json_encode($updateData)
            ]])
            ->andReturn($this->guzzleResponseMock);

        $response = $this->weeblyRepository->updateProduct($this->guzzleClientMock, $updateData, $this->site, $testProductId);
    }

    public function testRepositoryCanDeleteAProductSuccessfully () {
        $this->guzzleClientMock
            ->shouldReceive('request')
            ->withArgs(["DELETE", "https://api.weebly.com/v1/user/sites/{$this->site->weebly_site_id}/store/products/999999888888", [
                "headers" => [
                    "Accept" => "application/vnd.weebly.v1+json",
                    "Content-Type" => "application/json",
                    "X-Weebly-Access-Token" => $this->site->oauth_token
                ]
            ]])
            ->andReturn($this->guzzleResponseMock);
        $this->guzzleResponseMock->shouldReceive("getStatusCode")->andReturn(204);
        $productDeleted = $this->weeblyRepository->deleteProduct($this->guzzleClientMock, $this->site, "999999888888");
        $this->assertTrue($productDeleted);
    }

    public function testRepositoryReturnsFalseIfProductDeleteFails () {
        $this->guzzleClientMock
            ->shouldReceive('request')
            ->withArgs(["DELETE", "https://api.weebly.com/v1/user/sites/{$this->site->weebly_site_id}/store/products/999999888888", [
                "headers" => [
                    "Accept" => "application/vnd.weebly.v1+json",
                    "Content-Type" => "application/json",
                    "X-Weebly-Access-Token" => $this->site->oauth_token
                ]
            ]])
            ->andReturn($this->guzzleResponseMock);
        $this->guzzleResponseMock->shouldReceive("getStatusCode")->andReturn(500);
        $productDeleted = $this->weeblyRepository->deleteProduct($this->guzzleClientMock, $this->site, "999999888888");
        $this->assertFalse($productDeleted);
    }

    public function testRepositoryCanUpdateDashboardCard () {
        $testCardUpdate = [
            [
                "type" => "test",
                "value" => "This is just a component for the test dashboard card"
            ]
        ];
        $this->guzzleClientMock
            ->shouldReceive("request")
            ->withArgs(["PATCH", "https://api.weebly.com/v1/user/sites/{$this->site->weebly_site_id}/cards/999999888888", [
                "headers" => [
                    "Accept" => "application/vnd.weebly.v1+json",
                    "Content-Type" => "application/json",
                    "X-Weebly-Access-Token" => $this->site->oauth_token
                ],
                "body" => json_encode([
                    "hidden" => false,
                    "card_data" => $testCardUpdate
                ])
            ]])
            ->andReturn($this->guzzleResponseMock);
        $response = $this->weeblyRepository->updateDashboardCard($this->guzzleClientMock, $this->site, "999999888888", $testCardUpdate);
        $this->assertEquals($this->guzzleResponseMock, $response);
    }
}
