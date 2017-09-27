<?php

namespace Tests\Unit\Repositories;

use App\Site;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use App\Repositories\SitesRepository;

class SitesRepositoryTest extends TestCase
{
	private $site;

	public function setUp () {
		parent::setUp();
		$this->site = factory(Site::class)->create();
	}

	public function testUpdateOrCreateWillCreateANewSiteThatsNotAvailable () {
		$site = SitesRepository::updateOrCreate("1111111111", "2222222222");

		$this->assertEquals("1111111111", $site->weebly_site_id);
		$this->assertEquals("2222222222", $site->weebly_user_id);
		$this->assertCount(2, Site::all());

	}

	public function testUpdateOrCreateWillUpdateASiteWithGivenOauthToken () {
		$site = SitesRepository::updateOrCreate("1234567890", "5432109876", "ZYXWVUTSRQPONMLKJIHGFDECBA");

		$this->assertEquals("1234567890", $site->weebly_site_id);
		$this->assertEquals("5432109876", $site->weebly_user_id);
		$this->assertCount(1, Site::all());
	}
}
