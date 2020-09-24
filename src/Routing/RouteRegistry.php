<?php

namespace Api\Gateway\Routing;

use Api\Gateway\Services\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Lumen\Application;
use Webpatser\Uuid\Uuid;

/**
 * Class RouteRegistry
 * @package Api\Gateway\Routing
 */
class RouteRegistry
{
    /**
     * @var array
     */
    protected $routes = [];

    /**
     * RouteRegistry constructor.
     */
    public function __construct()
    {
        $this->parseConfigRoutes();
    }

    /**
     * @param RouteContract $route
     */
    public function addRoute(RouteContract $route)
    {
        $this->routes[] = $route;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->routes);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function getRoutes()
    {
        return collect($this->routes);
    }

    /**
     * @param string $id
     * @return RouteContract
     */
    public function getRoute($id)
    {
        return collect($this->routes)->first(function ($route) use ($id) {
            return $route->getId() == $id;
        });
    }

    /**
     * @param Application $app
     */
    public function bind(Application $app)
    {
        $this->getRoutes()->each(function ($route) use ($app) {
            $method = strtolower($route->getMethod());

            $middleware = [ 'helper:' . $route->getId() ];
    
            if (config('apigateway.logger.enable')) $middleware[] = 'logger';
            if (! $route->getRateLimit() > 0) $middleware[] = 'throttle:'. $route->getRateLimit() .',60';
            if (! $route->isPublic()) $middleware[] = 'auth';

            $middleware = is_array($route->getMiddleware())? array_merge($route->getMiddleware(), $middleware): $middleware;

            $app->router->{$method}($route->getPath(), [
                'uses' => 'Api\Gateway\Http\GatewayController@' . $method,
                'middleware' => $middleware
            ]);
        });
    }

    /**
     * @return $this
     */
    private function parseConfigRoutes()
    {
        $config = config('gateway');
        if (empty($config)) return $this;

        $this->parseRoutes($config['routes']);

        return $this;
    }

    /**
     * @param string $filename
     * @return RouteRegistry
     */
    public static function initFromFile($filename = null)
    {
        $registry = new self;
        $filename = $filename ?: 'routes.json';

        if (! Storage::exists($filename)) return $registry;
        $routes = json_decode(Storage::get($filename), true);
        if ($routes === null) return $registry;

        // We want to re-parse config routes to allow route overwriting
        return $registry->parseRoutes($routes)->parseConfigRoutes();
    }

    /**
     * [initFromModel description]
     * @param  [type] $route [description]
     * @return [type]        [description]
     */
    public static function initFromModel($route)
    {
        $routes = [];

        try {
            $registry = new self;

            if(Config::get('apigateway.cache', false)){
                $routes = Cache::remember('apigateway.routes', Config::get('apigateway.cache_lifetime'), 
                    function () use ($route) {
                        return (new $route)->with('services')->active()->get()->toArray();
                });
            }else{
                $routes = (new $route)->with('services')->active()->get()->toArray();
            }
        } catch (\Exception $e) {
            
        }
        
        return $registry->parseRoutes($routes)->parseConfigRoutes();
    }

    /**
     * @param array $routes
     * @return $this
     */
    private function parseRoutes(array $routes)
    {   
        collect($routes)->each(function ($routeDetails) {
            $routeDetails = (array) $routeDetails;
            if (! isset($routeDetails['id'])) {
                $routeDetails['id'] = (string) Uuid::generate(4);
            }
            
            $services = $routeDetails['services'];
            unset($routeDetails['services']);

            $route = new Route($routeDetails);
            collect($services)->each(function ($service) use ($route) {
                $route->addService(Service::createFromArray((array) $service));
            });

            $this->addRoute($route);
        });

        return $this;
    }
}
