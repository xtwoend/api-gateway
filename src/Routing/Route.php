<?php

namespace Api\Gateway\Routing;
use Api\Gateway\Presenters\JSONPresenter;
use Api\Gateway\Presenters\PresenterContract;
use Api\Gateway\Presenters\RawPresenter;
use Api\Gateway\Services\Service;
use Api\Gateway\Services\ServiceContract;

/**
 * Class Route
 * @package Api\Gateway\Routing
 */
class Route implements RouteContract
{
    /**
     * @const string
     */
    const DEFAULT_FORMAT = 'json';

    /**
     * @var array
     */
    protected $config;

    /**
     * @var PresenterContract
     */
    protected $presenter;

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
        $this->presenter = $this->isRaw() ? new RawPresenter() : new JSONPresenter();
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
        return $this->config['limit']?? -1;
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
        return $this->config['format']=='raw' ?? false;
    }

    /**
     * @return PresenterContract
     */
    public function getPresenter()
    {
        return $this->presenter;
    }

    /**
     * [FunctionName description]
     * @param string $value [description]
     */
    public function getContent()
    {
        return $this->config['content'] ?? '';
    }

    /**
     * [getAction description]
     * @return [type] [description]
     */
    public function getAction()
    {
        return $this->config['action']?? $this->config['path'];
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
        $this->middleware = explode(',', $this->config['middleware'])?? [];
        return $this->middleware;
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
        return $this->config['type']?? 'http';
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