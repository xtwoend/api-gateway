<?php

namespace Xtwoend\ApiGateway;

use Xtwoend\ApiGateway\Rpc\RpcClient;
use Xtwoend\ApiGateway\Router\RouteFactory;
use Xtwoend\ApiGateway\Router\RouteRegistry;
use Xtwoend\ApiGateway\Rpc\RpcClientInterface;
use Xtwoend\ApiGateway\Command\ClearCacheCommand;
use Xtwoend\ApiGateway\Listener\RegisterServiceListener;


class ConfigProvider
{
    public function __invoke()
    {
        return [
            'dependencies' => [
                RouteRegistry::class => RouteFactory::class,
                RpcClientInterface::class => RpcClient::class
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'listeners' => [
                RegisterServiceListener::class
            ],
            'commands' => [
                ClearCacheCommand::class
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for api gateway.',
                    'source' => __DIR__ . '/../publish/api-gateway.php',
                    'destination' => BASE_PATH . '/config/autoload/api-gateway.php',
                ],
                [
                    'id' => 'migrations',
                    'description' => 'Migrations for api gateway.',
                    'source' => __DIR__ . '/../migrations/2020_06_24_055916_create_routing_table.php',
                    'destination' => BASE_PATH . '/migrations/2020_06_24_055916_create_routing_table.php',
                ],
            ],
        ];
    }
}