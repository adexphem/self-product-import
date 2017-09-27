<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    protected $table = "sites";
    
	protected $fillable = [
		"weebly_site_id", "weebly_user_id", "oauth_token"
	];
}
