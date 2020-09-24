<?php

namespace Api\Gateway\Services;

use Ackintosh\Ganesha\Builder;
use Ackintosh\Ganesha\Exception\RejectedException;
use Ackintosh\Ganesha\GuzzleMiddleware;
use Api\Gateway\Exceptions\UnableToExecuteRequestException;
use Api\Gateway\Request;
use Api\Gateway\Routing\RouteContract;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Http\UploadedFile;

/**
 * Class RestClient
 * @package Api\Gateway\Services
 */
class RestClient
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var ServiceRegistryContract
     */
    protected $services;

    /**
     * @var array
     */
    protected $guzzleParams = [
        'headers' => [],
        'timeout' => 20,
        // 'debug' => true,
    ];

    /**
     * @var int
     */
    const USER_ID_ANONYMOUS = -1;

    /**
     * RestClient constructor.
     * @param Client $client
     * @param ServiceRegistryContract $services
     * @param Request $request
     */
    public function __construct(Client $client, ServiceRegistryContract $services, Request $request)
    {
        $this->client = $client;
        $this->services = $services;
        $this->injectHeaders($request);
        $this->guzzleParams['timeout'] = config('apigateway.global.timeout');
        $this->circuitBreaker();
    }

    /**
     * @param Request $request
     */
    private function injectHeaders(Request $request)
    {
        $this->setHeaders(
            [
                'X-User' => $request->user()->id ?? self::USER_ID_ANONYMOUS,
                'X-Token-Scopes' => $request->user() && ! empty($request->user()->token()) ? implode(',', $request->user()->token()->scopes) : '',
                'X-Client-Ip' => $request->getClientIp(),
                'User-Agent' => $request->header('User-Agent'),
                // 'Authorization' => $request->header('Authorization'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        );
    }

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        $this->guzzleParams['headers'] = $headers;
    }

    /**
     * @param $contentType
     * @return $this
     */
    public function setContentType($contentType)
    {
        $this->guzzleParams['headers']['Content-Type'] = $contentType;

        return $this;
    }

    /**
     * @param $contentSize
     * @return $this
     */
    public function setContentSize($contentSize)
    {
        $this->guzzleParams['headers']['Content-Length'] = $contentSize;

        return $this;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->guzzleParams['headers'];
    }

    /**
     * @param string $body
     * @return $this
     */
    public function setBody($body)
    {
        $this->guzzleParams['body'] = $body;

        return $this;
    }

    /**
     * @param string $body
     * @return $output
     */
    public function setAggregateOriginBody($body)
    {
        foreach (json_decode($body) as $key => $value) {
            $output['origin%' . $key] = $value;
         }

        return $output;
    }

    /**
     * @param array $files
     * @return $this
     */
    public function setFiles($files)
    {
        // Get rid of everything else
        $this->setHeaders(array_intersect_key($this->getHeaders(), ['X-User' => null, 'X-Token-Scopes' => null]));

        if (isset($this->guzzleParams['body'])) unset($this->guzzleParams['body']);

        $this->guzzleParams['timeout'] = 20;
        $this->guzzleParams['multipart'] = [];

        foreach ($files as $key => $file) {
            $this->guzzleParams['multipart'][] = [
                'name' => $key,
                'contents' => fopen($file->getRealPath(), 'r'),
                'filename' => $file->getClientOriginalName()
            ];
        }

        return $this;
    }

    /**
     * [circuitBreaker description]
     * @return [type] [description]
     */
    public function  circuitBreaker()
    {
        $redis = app('redis');
        $adapter = new RedisLumenAdapter($redis);

        $circuitBreaker = Builder::withRateStrategy()
            ->timeWindow(config('apigateway.circuit_breaker.time_window', 30))
            ->failureRateThreshold(config('apigateway.circuit_breaker.failure_rate_threshold', 50))
            ->minimumRequests(config('apigateway.circuit_breaker.minimum_request', 10))
            ->intervalToHalfOpen(config('apigateway.circuit_breaker.interval_to_halfopen', 10))
            ->adapter($adapter)
            ->build();

        $middleware = new GuzzleMiddleware($circuitBreaker);
        $handlers = HandlerStack::create();
        $handlers->push($middleware);

        $this->guzzleParams['handler'] = $handlers;
    }

    /**
     * @param $url
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function post($url)
    {
        return $this->client->post($url, $this->guzzleParams);
    }

    /**
     * @param $url
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function put($url)
    {
        return $this->client->put($url, $this->guzzleParams);
    }

    /**
     * @param $url
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function get($url)
    {  
        return $this->client->get($url, $this->guzzleParams);
    }

    /**
     * @param $url
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function delete($url)
    {
        return $this->client->delete($url, $this->guzzleParams);
    }

    /**
     * @param RouteContract $route
     * @param array $parametersJar
     * @return PsrResponse
     * @throws UnableToExecuteRequestException
     */
    public function syncRequest(RouteContract $route, $parametersJar)
    {   
        try {
            $response = $this->{strtolower($route->getMethod())}(
                $this->buildUrl($route, $parametersJar)
            );
        } catch (RejectedException $e) {
            // rejected service with circuit braker
            // TODO
            // set service jadi down.
            
            throw new UnableToExecuteRequestException();
        } catch (ConnectException $e) {
            throw new UnableToExecuteRequestException();
        } catch (RequestException $e) {
            return $e->getResponse();
        }

        return $response;
    }

    /**
     * @param string $url
     * @param array $params
     * @param string $prefix
     * @return string
     */
    private function injectParams($url, array $params, $prefix = '')
    {
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $url = $this->injectParams($url, $value, $prefix . $key . '.');
            }

            if (is_string($value) || is_numeric($value)) {
                $url = str_replace("{" . $prefix . $key . "}", $value, $url);
            }
        }

        return $url;
    }

    /**
     * @param array $body
     * @param array $params
     * @param string $prefix
     * @return array
     */
    private function injectBodyParams(array $body, array $params, $prefix = '')
    {
        foreach ($params as $key => $value) {
            foreach ($body as $bodyParam => $bodyValue) {
                if (is_string($value) || is_numeric($value)) {
                    $body[$bodyParam] = str_replace("{" . $prefix . $key . "}", $value, $bodyValue);
                } else if (is_array($value)) {
                    if ($bodyValue == "{" . $prefix . $key . "}") {
                        $body[$bodyParam] = $value;
                    }
                }
            }
        }
        return $body;
    }    

    /**
     * @param RouteContract $route
     * @param $parametersJar
     * @return string
     */
    private function buildUrl(RouteContract $route, $parametersJar)
    {
        $action = $route->getAction();
        $services = $route->getServices();
        $url = $this->injectParams($action, $parametersJar);
        if (isset($parametersJar['query_string'])) $url .= '?' . $parametersJar['query_string'];

        return $this->services->resolveInstance($services) . $url;
    }
}