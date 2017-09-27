<?php

namespace App\Helpers;

use App\Log as LogModel;
use  Illuminate\Http\Request as Request;

class SyncActivityLog
{
    const SOURCE_TYPE = "Weebly";
    const HMAC_VALID = "Success - HMAC Verified";
    const WEEBLY_PHASE_TWO_ACTION = "Weebly - Access Token Request";
    const WEEBLY_PHASE_ONE_ACTION = "Weebly OAuth Authentication - Phase One";
    const HMAC_INVALID = "Failed - Unable to verify HMAC. Request is invalid.";
    const ACCESS_TOKEN_GENERATED = "Success - Access token was successfully generated.";

    const SOURCE_PRODUCT_IMPORT_INITIATION_ACTION = "Initiating product import";
    const SOURCE_PRODUCT_IMPORT_INITIATION_STATUS = "Product import initiated Successfully";
    const SOURCE_PRODUCT_IMPORT_ACTION = "Importing product";
    const SOURCE_PRODUCT_IMPORT_STATUS = "Product imported successfully";

    /**
     * log - Adding activity logs to the log table
     *
     * @param obj $data
     * @returns void - only stores data in the log table
    */
    public function log($data) {
        LogModel::create((array) $data);
    }

    /**
     * formatLogData - format data to be logged in
     *
     * @param string $sourceType
     * @param string $requestAction
     * @param Illuminate\Http\Request $request
     *
     * @returns array
     */
    public function formatLogData(Request $request, string $sourceType, string $requestAction, string $requestResult) : array {
        $url = $request->fullUrl();
        $method = $request->getMethod();
        $ip = $request->getClientIp();
        $headers = $request->header();
        $data = $request->all();

        $rawRequest = json_encode([
            'ip' => $ip,
            'url' => $url,
            'method' => $method,
            'headers' => [
                'host' => $headers['host'],
                'user-agent' => $headers['user-agent']
            ],
            'data' => $data
        ]);

        return [
            "site_id" => $data['user_id'],
            "weebly_user_id" => $data['user_id'],
            "weebly_site_id" => $data['site_id'],
            "raw_request" => $rawRequest,
            "source_type" => $sourceType,
            "request_action" => $requestAction,
            "result" => $requestResult
        ];
    }
}