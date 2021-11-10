<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Xtwoend\ApiGateway\Http;

use GuzzleHttp\HandlerStack;
use Hyperf\Guzzle\PoolHandler;
use Hyperf\Guzzle\RetryMiddleware;
use Hyperf\Utils\Coroutine;

class GuzzleHandler
{
    public function __invoke(): HandlerStack
    {
        $handler = null;
        if (Coroutine::inCoroutine()) {
            $handler = make(PoolHandler::class, [
                'option' => [
                    'max_connections' => config('api-gateway.http.max_connection', 50),
                ],
            ]);
        }

        // Default retry Middleware
        $retry = make(RetryMiddleware::class, [
            'retries' => config('api-gateway.http.retries', 1),
            'delay' => config('api-gateway.http.delay', 5),
        ]);

        $stack = HandlerStack::create($handler);
        $stack->push($retry->getMiddleware(), 'retry');

        return $stack;
    }
}
