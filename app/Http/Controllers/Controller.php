<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use GuzzleHttp\Psr7\Response as GuzzleHttpResponse;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public $clientId;
    public $clientSecret;
    public $accessTokenURL;
    public $tokenSignature;
    public $weeblyHome;

    public function __construct () {
        $this->tokenSignature = [ env("JWT_APP_SIGNATURE") ];
        $this->accessTokenURL = env("WEEBLY_ACCESS_TOKEN_URL");
        $this->weeblyHome = env("WEEBLY_HOME_URL");
    }
}
