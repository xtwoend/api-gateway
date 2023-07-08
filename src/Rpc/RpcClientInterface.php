<?php

namespace Xtwoend\ApiGateway\Rpc;

interface RpcClientInterface
{
    public static function service(string $serviceName);
    public function params(...$args);
    public function call(string $method);

}