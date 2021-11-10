<?php

namespace Xtwoend\ApiGateway\Middleware;

use Swoole\Coroutine;
use Hyperf\Contract\ConfigInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Xtwoend\ApiGateway\Router\RouteRegistry;
use Hyperf\RateLimit\Handler\RateLimitHandler;
use Hyperf\RateLimit\Exception\RateLimitException;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use bandwidthThrottle\tokenBucket\storage\StorageException;

class RateLimitMiddleware implements MiddlewareInterface
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var RateLimitHandler
     */
    private $rateLimitHandler;

    public function __construct(ConfigInterface $config, RateLimitHandler $rateLimitHandler)
    {
        $this->config = $this->parseConfig($config);
        $this->rateLimitHandler = $rateLimitHandler;
    }

    /**
     * Undocumented function
     *
     * @param Request $request
     * @param Handler $handler
     * @return ResponseInterface
     */
    public function process(Request $request, Handler $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        return $this->rateLimit($request, $response);
    }

    public function rateLimit(Request $request, $response)
    {
        $bucketKey = $request->getUri()->getPath();

        $routeId = $request->getAttribute('route');
        $route = make(RouteRegistry::class)->getRoute($routeId);

        $create = $this->config['create'];
        $capacity = $route->getRateLimit();
        $consume = $this->config['consume'];
        $waitTimeout = $this->config['waitTimeout'];

        $bucket = $this->rateLimitHandler->build($bucketKey, $create, $capacity, $waitTimeout);

        $maxTime = microtime(true) + $waitTimeout;
        $seconds = 0;

        while (true) {
            try {
                if ($bucket->consume($consume ?? 1, $seconds)) {
                    return $response;
                }
            } catch (StorageException $exception) {
            }
            if (microtime(true) + $seconds > $maxTime) {
                break;
            }
            Coroutine::sleep($seconds > 0.001 ? $seconds : 0.001);
        }

        throw new RateLimitException('Service Unavailable.', 503);
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    protected function parseConfig(ConfigInterface $config)
    {
        if ($config->has('rate_limit')) {
            return $config->get('rate_limit');
        }

        return [
            'create' => 1,
            'consume' => 1,
            'capacity' => 2,
            'limitCallback' => [],
            'waitTimeout' => 10,
        ];
    }
}
