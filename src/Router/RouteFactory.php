<?php

namespace Xtwoend\ApiGateway\Router;

use Psr\Container\ContainerInterface;
use Xtwoend\ApiGateway\Router\RouteRegistry;

class RouteFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return RouteRegistry::init();
    }
}
