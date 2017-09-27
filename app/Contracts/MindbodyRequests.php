<?php

namespace App\Contracts;

trait MindbodyRequests {
	public function GetProductsRequestParameters (array $siteIDs) : array {
		return [
			"Request" => [
				'SourceCredentials' => [
					"SourceName" => env("MINDBODY_APP_SOURCE_NAME"),
					"Password" => env("MINDBODY_APP_PASSWORD"),
					"SiteIDs" => $siteIDs
				],
				'XMLDetail' => "Full",
				'SellOnline' => true
			]
		];
	}

	public function GetActivationCodeParameters (array $siteIDs) : array {
		return [
			"Request" => [
				'SourceCredentials' => [
					"SourceName" => env("MINDBODY_APP_SOURCE_NAME"),
					"Password" => env("MINDBODY_APP_PASSWORD"),
					"SiteIDs" => $siteIDs
				],
			]
		];
	}
}