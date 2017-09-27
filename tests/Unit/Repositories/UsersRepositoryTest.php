<?php

namespace Tests\Unit\Repositories;

use App\User;
use Tests\TestCase;
use App\Repositories\UsersRepository;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UsersRepositoryTest extends TestCase {

	public function setUp () {
		parent::setUp();
		factory(User::class)->create();
	}
	
	public function testRepositoryWillFindACreatedUserWithGivenParameters () {
		$user = UsersRepository::firstOrCreate("0246813579", "9876543210");

		$this->assertEquals("0246813579", $user->weebly_site_id);
		$this->assertEquals("9876543210", $user->weebly_user_id);
		$this->assertCount(1, User::all());
	}

	public function testRepositoryWillCreateNewUserWithNewParamaters () {
		$user = UsersRepository::firstOrCreate("3333333333", "2222222222");

		$this->assertEquals("3333333333", $user->weebly_site_id);
		$this->assertEquals("2222222222", $user->weebly_user_id);
		$this->assertCount(2, User::all());
	}
}