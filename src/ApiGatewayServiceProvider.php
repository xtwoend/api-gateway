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
            // return RouteRegistry::initFromDb(config('apigateway.table_name'));
        });

        $this->app->singleton(Request::class, function () {
            return $this->prepareRequest(Request::capture());
        });

        $this->app->bind(ServiceRegistryContract::class, DNSRegistry::class);

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
        })->setTrustedProxies([
            '10.7.0.0/16', // Docker Cloud
            '103.21.244.0/22', // Cloud Flare
            '103.22.200.0/22',
            '103.31.4.0/22',
            '104.16.0.0/12',
            '108.162.192.0/18',
            '131.0.72.0/22',
            '141.101.64.0/18',
            '162.158.0.0/15',
            '172.64.0.0/13',
            '173.245.48.0/20',
            '188.114.96.0/20',
            '190.93.240.0/20',
            '197.234.240.0/22',
            '198.41.128.0/17',
            '199.27.128.0/21',
            '172.31.0.0/16', // Rancher
            '10.42.0.0/16' // Rancher
        ], \Illuminate\Http\Request::HEADER_X_FORWARDED_ALL);

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