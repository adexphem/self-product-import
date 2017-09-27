<?php

namespace App\Contracts;

use App\Source;

abstract class Connector {

	public $clientId;
	public $clientSecret;
	public $cardId;

	public function __construct (string $id, string $secret, string $cardId) {
		$this->clientId = $id;
		$this->clientSecret = $secret;
		$this->cardId = $cardId;
	}

	abstract public function mapProduct (\stdClass $product) : \stdClass;

	abstract public function dashboardWelcomeCard (string $hostURL) : array;

	abstract public function dashboardSyncedCard (int $numberOfProductsSynced, string $hostURL) : array;

	abstract public function activateSource (Source $source, array $credentials) : bool;

	abstract public function deactivateSource (Source $source) : bool;
}