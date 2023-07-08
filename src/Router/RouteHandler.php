<?php

namespace Xtwoend\ApiGateway\Router;

use Hyperf\Utils\Codec\Json;
use Xtwoend\ApiGateway\Rpc\RpcClientInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use Xtwoend\ApiGateway\Http\HttpClientInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;


class RouteHandler
{
     /**
     * @var int
     */
    public const USER_ID_ANONYMOUS = -1;

    /**
     * @var int
     */
    public const PROJECT_ID_ANONYMOUS = -1;

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

            if ($this->route->getType() == 'rpc') {
                return $this->rpcHandler($request, $response);
            }

            $content = Json::encode($request->all());

            // http request
            $this->client->setBody($content);

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

    private function rpcHandler($request, $response)
    {
        $user = $request->getAttribute('user');
        $scopes = $request->getAttribute('oauth_scopes') ?? [];
        $project = $request->getAttribute('project_id');
        $locale = $request->getHeaderLine('accept-language') ?: 'id_ID';
        
        $query = $request->getQueryString();
        $body = $request->getBody();
        $headers = [
            'X-User' => ($user instanceof \OAuthServer\Entities\UserEntity) ? $user->getIdentifier() : self::USER_ID_ANONYMOUS,
            'X-Token-Scopes' => implode(',', $scopes),
            'X-Forwarded-For' => $request->getHeaderLine('x-forwarded-for'),
            'X-Project' => $project ?: self::PROJECT_ID_ANONYMOUS,
            'Accept-Language' => $locale,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $rpcClient = make(RpcClientInterface::class);
        $result = $rpcClient::service($this->route->getCurrentService()->name)
            ->params($query, $body, $headers)
            ->call($this->route->getAction());

        $response->getBody()->write(Json::encode($result));

        return $response
            ->withHeader('Content-Type', 'application/json;charset=utf-8')
            ->withStatus(200);
    }
}