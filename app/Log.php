<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $fillable = [
        'site_id',
        'weebly_user_id',
        'weebly_site_id',
        'source_type',
        'raw_request',
        'request_action',
        'result'
    ];
}
