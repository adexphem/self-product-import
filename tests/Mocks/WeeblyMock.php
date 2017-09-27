<?php

namespace Tests\Mocks;

use Faker\Factory;

class WeeblyMock {
	const WEEBLY_MOCK_SITE_ID = "123456789123456789";
	const WEEBLY_MOCK_USER_ID = "999999999";
	const WEEBLY_MOCK_JWT_TOKEN = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE1MDUwMjA0MDIsInVzZXJfaWQiOiI5OTk5OTk5OTkiLCJzaXRlX2lkIjoiMTIzNDU2Nzg5MTIzNDU2Nzg5IiwibGFuZ3VhZ2UiOiJlbiJ9.-9hHIpPJi77Z5rKgDMKUgrCJb-oF9PaEPZTEV2vBBcU";
	const WEEBLY_MOCK_HMAC = "48c4d382f78d8181da689cd956adfc52a68c9edfb08ec19d3fc267a4771c9f9f";
	const WEEBLY_MOCK_TIMESTAMP = "1500725164";
	const WEEBLY_MOCK_VERSION = "1.0.0";

	public $faker;

	public function __construct () {
		$this->faker = Factory::create();
	}

	private function createMindbodyMockProduct (int $count) : \stdClass {
		return (object) [
			"Price" => $this->faker->randomNumber(2),
			"TaxRate" => "0.13",
			"ID" => $count,
			"GroupID" => 98765,
			"Name" => $this->faker->name,
			"OnlinePrice" => $this->faker->randomNumber(2),
			"ShortDesc" => $this->faker->sentence(4), // 4 words for short description
			"LongDesc" => $this->faker->sentences(4, true), // 4 sentences for long description, as text
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

	public function mindbodyMockGetProductsResponse (int $numberOfProducts = 12) : \stdClass {
		return (object) [
			"GetProductsResult" => (object) [
				"Status" => "Success",
				"ErrorCode" => 200,
				"Products" => (object) [
					"Product" => (object) array_map(function (int $count) : \stdClass {
						return $this->createMindbodyMockProduct($count);
					}, range(1, $numberOfProducts))
				]
			]
		];
	}
}