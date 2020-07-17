<?php

namespace Api\Gateway\Routing;

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
            $middleware[] = 'throttle:'. $route->getRateLimit() .',60';
            $middleware[] = 'logger';

            if (! $route->isPublic()) $middleware[] = 'auth';

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
        $registry = new self;

        if(Config::get('apigateway.cache')){
            $routes = Cache::remember('apigateway.routes', Config::get('apigateway.cache_lifetime'), 
            function () use ($route) {
                return (new $route)->active()->get()->toArray();
            });
        }else{
            $routes = (new $route)->active()->get()->toArray();
        }

        return $registry->parseRoutes($routes)->parseConfigRoutes();
    }

    /**
     * [initFromDb description]
     * @param  [type] $tableName [description]
     * @return [type]            [description]
     */
    public static function initFromDb($tableName)
    {
        $registry = new self;

        if(Config::get('apigateway.cache')){
            $routes = Cache::remember('apigateway.routes', Config::get('apigateway.cache_lifetime'), 
            function () use ($tableName) {
                return DB::table($tableName)->where('active', 1)->get()->toArray();
            });
        }else{
            $routes = DB::table($tableName)->where('active', 1)->get()->toArray();
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
            $route = new Route($routeDetails);

            $this->addRoute($route);
        });

        return $this;
    }
}
