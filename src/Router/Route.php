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
namespace Xtwoend\ApiGateway\Router;


use Xtwoend\ApiGateway\Service\Service;
use Xtwoend\ApiGateway\Service\ServiceContract;


class Route implements RouteContract
{
    /**
     * @const string
     */
    public const DEFAULT_FORMAT = 'json';

    /**
     * @var array
     */
    protected $config;

    /**
     * [$services description]
     * @var [type]
     */
    protected $services = [];

    /**
     * current handler services
     */
    protected $currentService;

    /**
     * [$middleware description]
     * @var array
     */
    protected $middleware = [];

    /**
     * Route constructor.
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->config = $options;
        $this->middleware = $options['middleware'];
    }

    /**
     * @return string
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @inheritDoc
     */
    public function getId()
    {
        return $this->config['id'];
    }

    /**
     * [getRateLimit description]
     * @return [type] [description]
     */
    public function getRateLimit()
    {
        return $this->config['limit'] ?? -1;
    }

    /**
     * @inheritDoc
     */
    public function getMethod()
    {
        return $this->config['method'];
    }

    /**
     * @inheritDoc
     */
    public function getPath()
    {
        return $this->config['path'];
    }

    /**
     * @inheritdoc
     */
    public function isPublic()
    {
        return $this->config['public'] ?? false;
    }

    /**
     * @inheritdoc
     */
    public function isRaw()
    {
        return $this->config['format'] == 'raw' ?? false;
    }

    /**
     * [getHeaders description]
     * @return [type] [description]
     */
    public function getHeaders()
    {
        return $this->config['headers'] ?? '';
    }

    /**
     * [FunctionName description]
     * @param string $value [description]
     */
    public function getContent()
    {
        return $this->config['body'] ?? '';
    }

    /**
     * [getAction description]
     * @return [type] [description]
     */
    public function getAction()
    {
        return $this->config['action'] ?? $this->config['path'];
    }

    /**
     * [addService description]
     * @param [type] $service [description]
     */
    public function addService(ServiceContract $service)
    {
        $this->services[] = $service;

        return $this;
    }

    /**
     * [getCurrentHandler description]
     * @return [type] [description]
     */
    public function getCurrentService(): int
    {
        return $this->currentService ?? 0;
    }

    /**
     * [getMiddleware description]
     * @return [type] [description]
     */
    public function getMiddleware(): array
    {
        if (is_array($this->config['middleware'])) {
            $this->middleware = $this->config['middleware'];
        }else if(is_string($this->config['middleware'])) {
            $middleware = explode(',', $this->config['middleware']);
            $this->middleware = array_filter(
                $middleware,
                function ($value) {
                    return !is_null($value) && $value !== '';
                }
            );
        }
        return $this->middleware ?? [];
    }

    /**
     * [setCurrentHandler description]
     * @param int $index [description]
     */
    public function setCurrentService(int $index): void
    {
        $this->currentService = $index;
    }

    /**
     * [getHandler description]
     * @param  int    $index [description]
     * @return [type]        [description]
     */
    public function getService(int $index): ?Service
    {
        $services = $this->getServices();
        if (array_key_exists($index, $services)) {
            return $services[$index];
        }
        return null;
    }

    /**
     * [getServices description]
     * @return [type] [description]
     */
    public function getServices()
    {
        return collect($this->services);
    }

    /**
     * [getType description]
     * @return [type] [description]
     */
    public function getType()
    {
        return $this->config['type'] ?? 'http';
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->config['format'] ?? self::DEFAULT_FORMAT;
    }

    /**
     * @param string $format
     * @return $this
     */
    public function setFormat($format)
    {
        $this->config['format'] = $format;

        return $this;
    }
}