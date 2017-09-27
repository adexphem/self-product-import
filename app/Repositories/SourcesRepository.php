<?php

namespace App\Repositories;

use App\Source;

class SourcesRepository {

	const PENDING_SOURCE = "pending";
	const CONNECTED_SOURCE = "connected";
	const DISCONNECTED_SOURCE = "disconnected";

	public static function getSourceBySiteId (string $siteId) {
		return Source::where('site_id', $siteId)->first();
	}

	public static function firstOrCreateSource (string $siteId, string $sourceType) {
		return Source::firstOrCreate([
			"site_id" => $siteId,
			"type" => $sourceType
		]);
	}
}