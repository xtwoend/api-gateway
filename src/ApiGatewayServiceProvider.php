<?php

namespace Api\Gateway;

use Api\Gateway\Logger\DefaultLogWriter;
use Api\Gateway\Logger\LogHttpRequest;
use Api\Gateway\Logger\LogProfile;
use Api\Gateway\Logger\LogWriter;
use Api\Gateway\Request;
use Api\Gateway\Route;
use Api\Gateway\Routing\RouteRegistry;
use Api\Gateway\Services\DNSRegistry;
use Api\Gateway\Services\Resolver;
use Api\Gateway\Services\ServiceRegistryContract;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

/**
 * 
 */
class ApiGatewayServiceProvider extends ServiceProvider
{
	
	public function boot()
	{
        $this->publishConfig();

        $this->app->singleton(LogProfile::class, LogHttpRequest::class);
        $this->app->singleton(LogWriter::class, DefaultLogWriter::class);

		$this->app->singleton(RouteRegistry::class, function() {
            return RouteRegistry::initFromModel(Route::class);
        });

        $this->app->singleton(Request::class, function () {
            return $this->prepareRequest(Request::capture());
        });

        $this->app->bind(ServiceRegistryContract::class, Resolver::class);

        $this->app->singleton(Client::class, function() {
            return new Client([
                'timeout' => config('apigateway.global.timeout', 40),
                'connect_timeout' => config('apigateway.global.connect_timeout', 30),
                // 'debug' => true
            ]);
        });

        $this->app->alias(Request::class, 'request');

        $this->registerRoutes();
	}

    /**
     * [register description]
     * @return [type] [description]
     */
	public function register()
	{
		# code...
		
	}

    /**
     * [publishConfig description]
     * @return [type] [description]
     */
    public function publishConfig()
    {
        $this->publishes([
            __DIR__ . '/config/config.php' => config_path('apigateway.php')
        ]);

        $this->app->configure('apigateway');

        $this->mergeConfigFrom(
            __DIR__ . '/config/apigateway.php', 'apigateway'
        );
    }

	/**
     * Prepare the given request instance for use with the application.
     *
     * @param   Request $request
     * @return  Request
     */
    protected function prepareRequest(Request $request)
    {
        $request->setUserResolver(function () {
            return $this->app->make('auth')->user();
        })->setRouteResolver(function () {
            return $this->app->currentRoute;
        })->setTrustedProxies(config('apigateway.trusted_ips', []), \Illuminate\Http\Request::HEADER_X_FORWARDED_ALL);

        return $request;
    }

	public function registerRoutes()
	{
		$registry = $this->app->make(RouteRegistry::class);
        
        if ($registry->isEmpty()) {
            Log::info('Not adding any service routes - route file is missing');
            return;
        }

        $registry->bind(app());
	}
}