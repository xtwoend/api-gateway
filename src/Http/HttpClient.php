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

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Hyperf\HttpServer\Request;
use Hyperf\Utils\Collection;
use Xtwoend\ApiGateway\Exception\ServiceDownException;
use Xtwoend\ApiGateway\Exception\ServiceErrorException;
use Xtwoend\ApiGateway\Router\RouteContract;
use Xtwoend\ApiGateway\Service\Service;
use Xtwoend\ApiGateway\Service\ServiceRegistryContract;

class HttpClient implements HttpClientInterface
{
    /**
     * @var int
     */
    public const USER_ID_ANONYMOUS = -1;

    /**
     * @var int
     */
    public const PROJECT_ID_ANONYMOUS = -1;

    /**
     * @var \Hyperf\Guzzle\ClientFactory
     */
    protected $client;

    /**
     * @var \Xtwoend\ApiGateway\Service\ServiceRegistryContract
     */
    protected $services;

    /**
     * [$currentService description].
     * @var [type]
     */
    protected $currentService;

    /**
     * @var array
     */
    protected $guzzleParams = [
        'headers' => [],
        'multipart' => []
        // 'debug' => true,
    ];

    /**
     * Undocumented variable.
     *
     * @var \Hyperf\HttpServer\Request
     */
    protected $request;

    /**
     * RestClient constructor.
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
     * Undocumented function
     *
     * @param array $form
     * @return void
     */
    public function setMultipartData(array $form)
    {
        foreach ($form as $key => $value) {
            $this->guzzleParams['multipart'][] = [
                'name' => $key,
                'contents' => $value,
            ];
        }

        return $this;   
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function getMultipartData()
    {
        return $this->guzzleParams['multipart'];
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
     * Undocumented function.
     */
    public function getBody()
    {
        return $this->guzzleParams['body'] ?? null;
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
                'X-Token-Scopes' => null,
            ]
        ));

        if (isset($this->guzzleParams['body'])) {
            unset($this->guzzleParams['body']);
        }

        $this->guzzleParams['timeout'] = 20;
        // $this->guzzleParams['multipart'] = [];

        foreach ($files as $key => $file) {
            $this->guzzleParams['multipart'][] = [
                'name' => $key,
                'contents' => $file->getStream(),
                'filename' => $file->getClientFilename(),
            ];
        }

        return $this;
    }

    /**
     * @param $url
     * @param mixed $method
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function exec($method, $url)
    {
        return $this->client->{$method}($url, $this->guzzleParams);
    }

    /**
     * @param array $parametersJar
     * @throws ServiceDownException
     * @return ResponseInterface
     */
    public function request(RouteContract $route, $parametersJar)
    {
        try {
            $method = strtolower($route->getMethod());

            if ($method == 'any') {
                $method = $this->request->getMethod();
            }

            if (in_array($method, ['post', 'put', 'patch']) && isset($this->guzzleParams['body'])) {
                $this->setContentSize(strlen($this->getBody()));
            }

            if(empty($this->guzzleParams['multipart'])) {
                unset($this->guzzleParams['multipart']);
            }
        
            $url = $this->buildUrl($route, $parametersJar);

            return $this->exec($method, $url);
            
        } catch (ConnectException $th) {
            throw new ServiceDownException('Connection failed: ' . $th->getMessage());
        } catch (RequestException $th) {
            throw new ServiceErrorException();
        }
    }

    private function injectHeaders(Request $request)
    {
        $user = $request->getAttribute('user');
        $scopes = $request->getAttribute('oauth_scopes') ?? [];
        $project = $request->getAttribute('project_id');
        $locale = $request->getHeaderLine('accept-language') ?: 'id_ID';
        // $locale = locale_accept_from_http($locale);
       
        $this->setHeaders([
            'X-User' => ($user instanceof \OAuthServer\Entities\UserEntity) ? $user->getIdentifier() : self::USER_ID_ANONYMOUS,
            'X-Token-Scopes' => implode(',', $scopes),
            'X-Forwarded-For' => $request->getHeaderLine('x-forwarded-for'),
            'X-Project' => $project ?: self::PROJECT_ID_ANONYMOUS,
            'Accept-Language' => $locale,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);
    }

    /**
     * @param string $url
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
                if(str_contains($url, ':.+')) {
                    $url = str_replace(':.+', '', $url);
                }
                $url = str_replace('{' . $prefix . $key . '}', $value, $url);
            }
        }

        return $url;
    }

    /**
     * @param $parametersJar
     * @return string
     */
    private function buildUrl(RouteContract $route, $parametersJar)
    {
        $action = $route->getAction();
        $services = $route->getServices();
        $method = $route->getMethod();

        if ($services->isEmpty()) {
            throw new \Exception('No services available');
        }

        $url = $this->injectParams($action, $parametersJar);

        if (strtolower($method) == 'any') {
            $route = $this->request->route('route');
            $url = str_replace('{route:.+}', $route, $url);
        }

        if (isset($parametersJar['query_string'])) {
            $url .= '?' . $parametersJar['query_string'];
        }

        $service = $this->resolover($services);

        return $service->getUrl() . $url;
    }

    /**
     * Undocumented function.
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
