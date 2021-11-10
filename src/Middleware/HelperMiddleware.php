<?php

namespace Xtwoend\ApiGateway\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class HelperMiddleware implements MiddlewareInterface
{
    public function process(Request $request, Handler $handler): ResponseInterface
    {
        $dispatched = $request->getAttribute(\Hyperf\HttpServer\Router\Dispatched::class);
        $route = $dispatched->handler->options['route'];

        $request = $request->withAttribute('route', $route);
        $request = $request->withAttribute('params', $dispatched->params);

        return $handler->handle($request);
    }
}
