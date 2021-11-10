<?php

namespace Xtwoend\ApiGateway\Router;

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
        $this->client->setBody($request->getBody()->getContents());

        if (count($request->getUploadedFiles()) > 0) {
            $this->client->setFiles($request->getUploadedFiles());
        }

        $parametersJar = $this->parametersJar($request);
        $serviceResponse = $this->client->request($this->route, $parametersJar);
        
        if ($serviceResponse && $serviceResponse->getStatusCode() >= 500) {
            return $response->json([
                'error' => 500,
                'message' => 'ops.. someting wrong.',
            ], 500);
        }

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
}