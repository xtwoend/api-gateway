<?php

namespace Xtwoend\ApiGateway\Router;

use Throwable;
use Hyperf\DbConnection\Db;
use Hyperf\Utils\Collection;
use Xtwoend\ApiGateway\Router\Route;
use Xtwoend\ApiGateway\Http\HttpClient;
use Xtwoend\ApiGateway\Service\Service;
use Xtwoend\ApiGateway\Service\Resolver;
use Xtwoend\ApiGateway\Router\RouteHandler;
use Xtwoend\ApiGateway\Router\RouteContract;
use Xtwoend\ApiGateway\Router\RouteRegistry;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;


class RouteRegistry
{
    /**
     * @var array
     */
    protected $routes = [];

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
     * @return \Hyperf\Utils\Collection
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
    
    public function make($router)
    {   
        $this->getRoutes()->each(function ($route) use ($router) {

            $middleware = [
                \Xtwoend\ApiGateway\Middleware\HelperMiddleware::class,
                // \App\Middleware\SubDomainMiddleware::class,
                // \App\Middleware\ClientMiddleware::class,
            ];

            if(config('api-gateway.middleware.project')) {
                $middleware[] = config('api-gateway.middleware.project');
            }

            if ($route->getRateLimit() > 0) {
                $middleware[] = \Xtwoend\ApiGateway\Middleware\RateLimitMiddleware::class;
            }

            if (! $route->isPublic()) {
                $middleware[] = config('api-gateway.middleware.user');
            }

            if(in_array('client', $route->getMiddleware())) {
                $middleware[] = config('api-gateway.middleware.client');
            }
            
            foreach ($route->getMiddleware() as $mid) {
                if (class_exists($mid)) {
                    array_push($middleware, $mid);
                }
            }

            $method = strtoupper($route->getMethod());
            $method = ($method == 'ANY') ? ['GET','POST','PUT','DELETE'] : $method;

            $router::addRoute(
                $method,
                $route->getPath(),
                function (RequestInterface $request, ResponseInterface $response) {
                    $client = new HttpClient(new Resolver(), $request);

                    $routeId = $request->getAttribute('route');
                    $route = make(RouteRegistry::class)->getRoute($routeId);

                    $config = $route->getConfig();
                    $services = $route->getServices();

                    $handler = new RouteHandler($client, $route, $config, $services);

                    return $handler->request($request, $response);
                },
                [
                    'middleware' => array_unique($middleware),
                    'route' => $route->getId()
                ]
            );
        });
    }

    /**
     * [init description]
     * @param  string $route [description]
     * @return [type]        [description]
     */
    public static function init()
    {
        $routes = [];

        try {
            $registry = new self();
            if (config('api-gateway.cache', false)) {
                if (! cache()->has('apigateway.routes')) {
                    cache()->set('apigateway.routes', $registry->queryRoute(), config('api-gateway.cache_lifetime'));
                }
                $routes = cache()->get('apigateway.routes');
            } else {
                $routes = $registry->queryRoute();
            }
        } catch (Throwable $e) {
        }
        
        return $registry->parseRoutes($routes);
    }

    public function queryRoute()
    {
        $routes = Db::table('routes')->where('active', 1)->get();
        $ids = $routes->pluck('id');

        $services = Db::table('services')
            ->select(
                'services.*',
                'route_services.route_id as pivot_route_id',
                'route_services.service_id as pivot_service_id'
            )
            ->join('route_services', 'services.id', '=', 'route_services.service_id')
            ->whereIn('route_services.route_id', $ids)
            ->get();

        foreach ($routes as $index => $route) {
            $relation = clone $services;
            $filter = $relation->where('pivot_route_id', $route->id);
            $routes[$index]->services = $filter->values();
        }

        return $routes->toArray();
    }

    /**
     * @param array $routes
     * @return $this
     */
    private function parseRoutes(array $routes)
    {
        collect($routes)->each(function ($routeDetails) {
            $routeDetails = (array) $routeDetails;

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