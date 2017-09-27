<?php

namespace App\Providers;

use \App\Contracts\Connector;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        Route::pattern('source_connector', '(shapeways|mindbody)');

        parent::boot();

        Route::bind('source_connector', function (string $source) : Connector {
            $dashboardAppCredentials = $this->getCredentials($source);
            $class = "App\Contracts\\" . ucfirst(strtolower($source)) . ucfirst("connector");
            return new $class($dashboardAppCredentials["id"], $dashboardAppCredentials["secret"], $dashboardAppCredentials["cardId"]);
        });

    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();

        $this->mapWebRoutes();

        //
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
             ->namespace($this->namespace)
             ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
             ->middleware('api')
             ->namespace($this->namespace)
             ->group(base_path('routes/api.php'));
    }

    private function getCredentials (string $sourceConnectorName) : array {
        $name = strtoupper($sourceConnectorName);
        return [
            "id" => env("WEEBLY_" . $name . "_CLIENT_ID"),
            "secret" => env("WEEBLY_" . $name . "_CLIENT_SECRET"),
            "cardId" => env("WEEBLY_" . $name . "_DASHBOARD_CARD_ID")
        ];
    }
}
