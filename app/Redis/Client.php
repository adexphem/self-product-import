<?php

namespace App\Redis;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class Client
{
    private $cacheExpiryTime;

    public function __construct() {
        // todo if need be to pre-assign values at instantiation stage, will be nice to add more here
        $this->cacheExpiryTime = env('REDIS_CACHE_EXPIRES');
    }

    /**
     * Gets data from the class instance and save in redis with key (siteIduserId) concatenated
     * @param array $data
     *
     * @return array $response
    */
    public function store($data) {
        $key = $this->composeKey($data['site_id'],$data['user_id']);
        Redis::hmset($key, $data);
        Redis::expire($key, $this->cacheExpiryTime);
        $response = $this->find($key);

        return $response;
    }

    /**
     * To get all fields and values in a hash - redis
     * @param string $key
     *
     * @return array
    */
    public function find($key)
    {
        $response = Redis::hgetall($key);
        return (!empty($response) ? $response : ['status' => false]);
    }

    public function updateCertainPath($key, $path, $value)
    {
        Redis::hmset($key, [
            $path => $value
        ]);

        Redis::expire($key, $this->cacheExpiryTime);

        $response = $this->find($key);
        return $response;
    }

    /**
     * composeKey - concatenated site and user id
     * @param string $siteId
     * @param string $userId
     *
     * @return string
     */
    public function composeKey(string $siteId, string $userId) :string {
        return $siteId.$userId;
    }

}
