<?php

namespace Xtwoend\ApiGateway\Http;

use GuzzleHttp\Client;
use Hyperf\Utils\Collection;
use Hyperf\HttpServer\Request;
use Hyperf\Guzzle\ClientFactory;
use Xtwoend\ApiGateway\Service\Service;
use GuzzleHttp\Exception\ConnectException;
use Xtwoend\ApiGateway\Http\GuzzleHandler;
use Xtwoend\ApiGateway\Router\RouteContract;
use Xtwoend\ApiGateway\Http\HttpClientInterface;
use Xtwoend\ApiGateway\Exception\ServiceDownException;
use Xtwoend\ApiGateway\Service\ServiceRegistryContract;


class HttpClient implements HttpClientInterface
{
    /**
     * @var \Hyperf\Guzzle\ClientFactory
     */
    protected $client;

    /**
     * @var \Xtwoend\ApiGateway\Service\ServiceRegistryContract
     */
    protected $services;

    /**
     * [$currentService description]
     * @var [type]
     */
    protected $currentService;

    /**
     * @var array
     */
    protected $guzzleParams = [
        'headers' => [],
        // 'debug' => true,
    ];

    /**
     * Undocumented variable
     *
     * @var \Hyperf\HttpServer\Request
     */
    protected $request;

    /**
     * @var int
     */
    public const USER_ID_ANONYMOUS = -1;

    /**
     * RestClient constructor.
     * @param ServiceRegistryContract $services
     * @param Request $request
     */
    public function __construct(
        ServiceRegistryContract $services,
        Request $request
    ) {
        $this->client = make(Client::class, [
            'timeout' => config('guzzle.timeout'),
            'config' => [
                'handler' => (new GuzzleHandler())(),
            ],
        ]);

        $this->services = $services;
        $this->request = $request;

        $this->injectHeaders($request);
    }

    /**
     * @param \Hyperf\HttpServer\Request $request
     */
    private function injectHeaders(Request $request)
    {
        $user = $request->getAttribute('user');
        $scopes = $request->getAttribute('oauth_scopes') ?? [];
        $project = $request->getAttribute('project') ?? "{}";

        [$locale] = explode("_", locale_accept_from_http($request->getHeaderLine('accept-language', 'id')));

        $this->setHeaders([
            'X-User' => $user->id ?? self::USER_ID_ANONYMOUS,
            'X-Token-Scopes' => implode(",", $scopes),
            'X-Forwarded-For' => $request->getAttribute('ip'),
            'X-Project' =>  $project,
            'Accept-Language' => $locale,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ]);
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
     * Undocumented function
     *
     * @return void
     */
    public function getBody()
    {
        return $this->guzzleParams['body'];
    }

    /**
     * @param array $files
     * @return $this
     */
    public function setFiles($files)
    {
        // Get rid of everything else
        $this->setHeaders(array_intersect_key(
            $this->getHeaders(),
            [
                'X-User' => null,
                'X-Token-Scopes' => null
            ]
        ));

        if (isset($this->guzzleParams['body'])) {
            unset($this->guzzleParams['body']);
        }

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
     * @param $url
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function exec($method, $url)
    {
        return $this->client->{$method}($url, $this->guzzleParams);
    }

    /**
     * @param RouteContract $route
     * @param array $parametersJar
     * @return ResponseInterface
     * @throws ServiceDownException
     */
    public function request(RouteContract $route, $parametersJar)
    {
        try {
            $method = strtolower($route->getMethod());
            
            if($method == 'any') {
                $method = $this->request->getMethod();
            }

            if (in_array($method, ['post', 'put', 'patch'])) {
                $this->setContentSize(strlen($this->getBody()));
            }

            $url = $this->buildUrl($route, $parametersJar);
            
            $response = $this->exec($method, $url);

        } catch (ConnectException $th) {
            throw new ServiceDownException('Connection failed: ' . $th->getMessage());
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
     * @param RouteContract $route
     * @param $parametersJar
     * @return string
     */
    private function buildUrl(RouteContract $route, $parametersJar)
    {
        $action = $route->getAction();
        $services = $route->getServices();
        $method = $route->getMethod();

        if ($services->isEmpty()) {
            throw new \Exception("No services available");
        }

        $url = $this->injectParams($action, $parametersJar);
        
        if(strtolower($method) == 'any') {
            $route = $this->request->route('route');
            $url = str_replace("{route:.+}", $route, $url);
        }

        if (isset($parametersJar['query_string'])) {
            $url .= '?' . $parametersJar['query_string'];
        }

        $service = $this->resolover($services);

        return $service->getUrl() . $url;
    }

    /**
     * Undocumented function
     *
     * @param \Hyperf\Utils\Collection $services
     * @return Service
     */
    private function resolover(Collection $services): Service
    {
        //versioning services
        $version = $this->request->getHeaderLine('accept-version');

        // TODO: handle versioning
        $filtered = $services->filter(function ($value, $key) use ($version) {
            return $value->getVersion() == $version;
        });

        if ($filtered->count() > 0) {
            $services = $filtered;
        }

        $service = $this->services->resolveInstance($services);

        // set current service for manage error service
        $this->currentService = $service;

        return $service;
    }
}