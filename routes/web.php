<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// OAuth
Route::prefix("/auth")->group(function () {
    Route::get("/error", "Auth\OAuthController@error")->name("oauth.error");
    Route::prefix("/{source_connector}")->group(function () {
        Route::get("/authorize", "Auth\OAuthController@phaseOne")->name("oauth.phase.one");
        Route::get("/phasetwo", "Auth\OAuthController@phaseTwo")->name("oauth.phase.two");
    });
});

// source_connector => (shapeways|mindbody). See RouteServiceProvider
Route::prefix("/{source_connector}")->group(function () {
    Route::get("/dashboard", "SourcesController@index")->name("source.dashboard");
    Route::get("/dashboard/sync", "SourcesController@initiateSync")->name("source.start.sync");
    Route::get("/connect", "Auth\OAuthController@connect")->name("source.connect");
    Route::get('/callback', "Auth\OAuthController@sourceConnectCallback")->name('source.connect.callback');
    Route::get("/sync", "SourcesController@sync")->name("source.sync");
    Route::post("/activationcode", "Auth\OAuthController@activationCode")->name('get.activation.code');
    Route::get("/finish_connection", "Auth\OAuthController@finishConnection")->name('source.finish.connection');
});

