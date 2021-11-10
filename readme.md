## Api Gateway Module for Hyperf Framework


### Install

```
    composer require xtwoend/api-gateway
```


```
    php bin/hyperf.php vendor:publish xtwoend/api-gateway
    php bin/hyperf.php migrate
```

in config/routes.php

```
    use Xtwoend\ApiGateway\Router\RouteRegistry;
    make(RouteRegistry::class)->make(Router::class);
```