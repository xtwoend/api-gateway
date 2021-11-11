<?php

namespace Xtwoend\ApiGateway\Router;

use Hyperf\Utils\Codec\Json;
use Hyperf\HttpServer\Contract\RequestInterface;
use Xtwoend\ApiGateway\Http\HttpClientInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;


class RouteHandler
{
    protected $client;
    protected $route;
    protected $config;
    protected $services;

    public function __construct(HttpClientInterface $client, $route, $config, $services)
    {
        $this->client = $client;
        $this->route = $route;
        $this->config = $config;
        $this->services = $services;
    }

    public function request(RequestInterface $request, ResponseInterface $response)
    {
        try {

            // mock request
            if ($this->route->getType() === 'mock') {
                return $this->mockRequest($request, $response);
            }

            // http request
            $this->client->setBody($request->getBody()->getContents());

            if (count($request->getUploadedFiles()) > 0) {
                $this->client->setMultipartData($request->all());
                $this->client->setFiles($request->getUploadedFiles());
            }

            $parametersJar = $this->parametersJar($request);
            $serviceResponse = $this->client->request($this->route, $parametersJar);
            
            $content = $serviceResponse->getBody()->getContents();
            $response->getBody()->write($content);

            foreach ($serviceResponse->getHeaders() as $key => $value) {
                if (
                    in_array($key, [
                        'content-encoding',
                        'content-length',
                        'content-type',
                        'transfer-encoding',
                        'set-cookie'
                    ])
                ) {
                    continue;
                }

                $response = $response->withHeader($key, $value[0]);
            }

            return $response
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->withStatus($serviceResponse->getStatusCode());

        } catch (\Throwable $th) {

            $response->getBody()->write(Json::encode([
                'error' => $th->getCode(),
                'message' => $th->getMessage(), 
            ]));
        
            return $response
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->withStatus(503);
        }
    }

    private function parametersJar(RequestInterface $request)
    {
        return array_merge(
            $request->getAttribute('params'),
            [
                'query_string' => $request->getQueryString()
            ]
        );
    }

    private function mockRequest($request, $response)
    {
        $start = microtime(true);
        $parametersJar = $this->parametersJar($request);
        $execution = microtime(true) - $start;
        
        $defHeaders = json_decode($this->route->getHeaders());
        
        $headers = array_merge([
            'X-Time-Execution' => $execution,
            'X-Mode' => $this->route->getType()
        ], $defHeaders);

        $content = $this->route->getContent();


        foreach ($headers as $header => $value) {
            $response = $response->withHeader($header, $value);
        }
        
        $response->getBody()->write($content);

        return $response
            ->withHeader('Content-Type', 'application/json;charset=utf-8')
            ->withStatus(200);
    }
}