<?php

namespace App;

use Source;
use Illuminate\Database\Eloquent\Model;

class ProductMapping extends Model
{
    protected $fillable = [
        'source_product_id',
        'weebly_product_id',
        'source_id'
    ];

    /**
     * doesProductExist - to check the existence of a product
     * @param int $productId
     *
     * @return bool
    */
    public static function doesProductExist($productId): bool {
        $res = false;
        $result = ProductMapping::where('source_product_id', $productId)->first();

        if ($result) {
            $res = true;
        }

        return $res;
    }

    public function source () {
        return $this->belongsTo(Source::class);
    }
}
