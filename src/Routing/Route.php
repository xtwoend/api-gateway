<?php

namespace Api\Gateway\Routing;
use Api\Gateway\Presenters\JSONPresenter;
use Api\Gateway\Presenters\PresenterContract;
use Api\Gateway\Presenters\RawPresenter;

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
     * [$rateLimit description]
     * @var [type]
     */
    protected $rateLimit;

    /**
     * Route constructor.
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->config = $options;
        $this->rateLimit = config('apigateway.rate_limit', 500);
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
        return $this->rateLimit;
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
     * [getUrl description]
     * @return [type] [description]
     */
    public function getUrl()
    {
        if($this->config['type'] == 'http'){
            return $this->config['content'] ?? '';
        }
        
        return '';
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