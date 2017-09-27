<?php

namespace App\Services;

class WeeblyProductUpdateService {

	public $site_id;
	public $site_product_id;
	public $name;
	public $short_description;
	public $published;
	public $taxable;

	public function __construct (string $site_id, string $site_product_id, string $name, string $short_description, bool $published = false, bool $taxable = false) {
		$this->site_id = $site_id;
		$this->site_product_id = $site_product_id;
		$this->name = $name;
		$this->short_description = $short_description;
		$this->published = $published;
		$this->taxable = $taxable;
	}
}