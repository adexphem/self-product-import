<?php

namespace Tests\Feature;

use App\Site;
use Mockery as M;
use Tests\TestCase;
use Illuminate\Http\Request;
use App\Contracts\Connector;
use App\Exceptions\InvalidHmacException;
use App\Helpers\SyncActivityLog;
use App\Repositories\SitesRepository;
use App\Repositories\WeeblyRepository;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class OAuthControllerTest extends TestCase
{
	private $userId = "999999999";
	private $siteId = "123456789123456789";
	private $hmac = "48c4d382f78d8181da689cd956adfc52a68c9edfb08ec19d3fc267a4771c9f9f";
	private $timestamp = "1500725164";
	private $version = "1.0.0";

	public function setUp () {
		$this->testReferer = "__TEST_REFERER__";
		parent::setUp();
	}

	public function getOAuthMockRequestParams () : array {
		return [
			"user_id" => $this->userId,
			"timestamp" => $this->timestamp,
			"site_id" => $this->siteId,
			"version" => $this->version,
			"hmac" => $this->hmac,
			"callback_url" => "https://www.weebly.com/app-center/oauth/authorize"
		];
	}

	public function testWeeblyOAuthPhaseOneWithValidHmacParameterCreatesANewSiteAndRedirectsToWeebly () {
		$params = $this->getOAuthMockRequestParams();

		$response = $this->get(route("oauth.phase.one", array_merge([
			'source_connector' => "mindbody"
		], $params)), [
		    "referer" => $this->testReferer
        ]);

		$redirectURI = "https://www.weebly.com/app-center/oauth/authorize?client_id=APP_TEST_ID&user_id={$this->userId}&timestamp={$this->timestamp}&site_id={$this->siteId}&version={$this->version}&scope=read%3Astore-catalog%2Cwrite%3Astore-catalog&redirect_uri=http%3A%2F%2Flocalhost%2Fauth%2Fmindbody%2Fphasetwo";

		$response->assertRedirect($redirectURI);

		// assert new Site was created
		$this->assertCount(1, Site::all());

		// assert this action was logged with Success Hmac verification.
		$lastActionLog = \App\Log::all()->last();
		$rawRequestURL = "http://localhost/auth/mindbody/authorize?callback_url=https%3A%2F%2Fwww.weebly.com%2Fapp-center%2Foauth%2Fauthorize&hmac={$this->hmac}&site_id={$this->siteId}&timestamp={$this->timestamp}&user_id={$this->userId}&version={$this->version}";

		$rawRequest = json_decode($lastActionLog->raw_request);

		$this->assertEquals($rawRequestURL, $rawRequest->url);
		$this->assertEquals([ $this->testReferer ], $rawRequest->headers->referer);
		$this->assertEquals("GET", $rawRequest->method);
		$this->assertEquals("127.0.0.1", $rawRequest->ip);

		$this->assertEquals("Weebly OAuth Authentication - Phase One", $lastActionLog->request_action);
		$this->assertEquals("Success - HMAC Verified", $lastActionLog->result);
		$this->assertEquals("Weebly", $lastActionLog->source_type);
	}

	public function testWeeblyOAuthPhaseOneWithInvalidHmacParameterRedirectsToErrorPage () {
		$params = $this->getOAuthMockRequestParams();
		$params["hmac"] = "invalid hmac";

		$response = $this->get(route("oauth.phase.one", array_merge([
			'source_connector' => "mindbody"
		], $params)), [
			'referer' => $this->testReferer
		]);

		$response->assertRedirect(route("oauth.error"));

		// assert new Site was not created
		$this->assertCount(0, Site::all());

		// assert this action was logged with Failed Hmac verification.
		$lastActionLog = \App\Log::all()->last();
		$rawRequestURL = "http://localhost/auth/mindbody/authorize?callback_url=https%3A%2F%2Fwww.weebly.com%2Fapp-center%2Foauth%2Fauthorize&hmac=invalid%20hmac&site_id={$this->siteId}&timestamp={$this->timestamp}&user_id={$this->userId}&version={$this->version}";

		$rawRequest = json_decode($lastActionLog->raw_request);

		$this->assertEquals($rawRequestURL, $rawRequest->url);
		$this->assertEquals([ $this->testReferer ], $rawRequest->headers->referer);
		$this->assertEquals("GET", $rawRequest->method);
		$this->assertEquals("127.0.0.1", $rawRequest->ip);

		$this->assertEquals("Weebly OAuth Authentication - Phase One", $lastActionLog->request_action);
		$this->assertEquals("Failed - Unable to verify HMAC. Request is invalid.", $lastActionLog->result);
		$this->assertEquals("Weebly", $lastActionLog->source_type);
	}
}
