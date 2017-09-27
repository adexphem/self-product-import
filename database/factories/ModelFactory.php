<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Site::class, function (Faker\Generator $faker) {
	return [
		"weebly_site_id" => "1234567890",
		"weebly_user_id" => "5432109876",
		"oauth_token" => "ABCEDFGHIJKLMNOPQRSTUVWXYZ"
	];
});

$factory->define(App\User::class, function (Faker\Generator $faker) {
	return [
		"weebly_site_id" => "0246813579",
		"weebly_user_id" => "9876543210"
	];
});

$factory->define(App\Source::class, function (Faker\Generator $faker) {
	return [
		"site_id" => "123456",
		"type" => "weeblysource",
		"activation_status" => "connected",
		"credentials" => json_encode([
			// for shapeways
			"access_token" => "__TEST_ACCESS_TOKEN_FOR_SHAPEWAYS__",
			"token_type" => "bearer",
			"expires_in" => 3600,
			"refresh_token"=> "__TEST_REFRESH_TOKEN_FOR_SHAPEWAYS__",
			
			// for mindbody
			"studio_id" => "-99999"
		])
	];
});
