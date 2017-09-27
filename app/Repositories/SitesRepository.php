<?php

namespace App\Repositories;

use App\Site;

class SitesRepository {

	public static function updateOrCreate (string $siteId, string $userId, $token = null) {
		$site = Site::updateOrCreate([
			"weebly_site_id" => $siteId,
			"weebly_user_id" => $userId
		]);
		if (!is_null($token)) {
			$site->update([
				"oauth_token" => $token
			]);
		}
		return $site;
	}

	public static function getSiteBySiteId (string $siteId) : Site {
		return Site::where("weebly_site_id", $siteId)->first();
	}
}