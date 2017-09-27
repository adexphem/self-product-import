<?php

namespace App;

use App\ProductMapping;
use Illuminate\Database\Eloquent\Model;

class Source extends Model
{
    protected $table = 'sources';

    protected $fillable = [
    	'site_id', 'type', 'credentials', 'activation_status'
    ];

    public function productMappings () {
    	return $this->hasMany(ProductMapping::class);
    }

    public function incrementSyncCount (int $count) {
    	$this->synced_products_no += $count;
    	$tihs->save();
    }

    public function decrementSyncCount (int $count) {
    	$this->synced_products_no -= $count;
    	$tihs->save();
    }

    public function syncCount () : int {
    	return $this->productMappings()->count();
    }

    public function saveCount () {
    	$this->synced_products_no = $this->syncCount();
    	$this->save();
    }
}
