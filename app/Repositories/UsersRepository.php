<?php

namespace App\Repositories;

use App\User;

class UsersRepository {
	
	public static function firstOrCreate (string $siteId, string $userId) : User {
		return User::firstOrCreate([
			"weebly_site_id" => $siteId,
			"weebly_user_id" => $userId
		]);
	}
}