<?php

namespace Api\Gateway\Services;

use Curl\Curl;


/**
 * HTTP Client base On Curl and PSR-18
 */
class HttpClient
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
     * [$headers description]
     * @var array
     */
    protected $headers = [];

    /**
     * @var int
     */
    const USER_ID_ANONYMOUS = -1;

    /**
     * [__construct description]
     */
	public function __construct(Curl $client, ServiceRegistryContract $services, Request $request)
	{
		$this->client = $client;
		$this->services = $services;
		$this->injectHeaders($request);
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
    	$this->headers = $headers;
    }

    /**
     * [FunctionName description]
     * @param string $value [description]
     */
    public function setContentType($contentType)
    {
        $this->headers['Content-Type'] = $contentType;
        return $this;
    }

    /**
     * [setContentSize description]
     * @param string $value [description]
     */
    public function setContentSize($contentSize)
    {
        $this->headers['Content-Length'] = $contentSize;
        return $this;
    }

    /**
     * [getHeaders description]
     * @return [type] [description]
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * [setBody description]
     * @param [type] $body [description]
     */
    public function setBody($body)
    {
        return $this;
    }

    /**
     * [setAggregateOriginBody description]
     */
    public function setAggregateOriginBody($body)
    {
        foreach (json_decode($body) as $key => $value) {
            $output['origin%' . $key] = $value;
         }

        return $output;
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