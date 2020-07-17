<?php

namespace Api\Gateway\Middleware;


use Api\Gateway\Request;
use Api\Gateway\Routing\RouteRegistry;
use Closure;

class HelperMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param  \Closure  $next
     * @param string $id
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $id)
    {   
        $request->attachRoute(
            app()->make(RouteRegistry::class)->getRoute($id)
        );

        return $next($request);
    }
}