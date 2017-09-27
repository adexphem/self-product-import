<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'weebly_site_id', 'weebly_user_id'
    ];

    /**
     * Set the property to use for user authentication.
     */
    public function username () : string {
        return 'weebly_user_id';
    }
}
