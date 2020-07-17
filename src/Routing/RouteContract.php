<?php

namespace Api\Gateway\Routing;

use Api\Gateway\Presenters\PresenterContract;
use Illuminate\Support\Collection;

/**
 * Interface RouteContract
 * @package Api\Gateway\Routing
 */
interface RouteContract
{
    /**
     * @return string
     */
    public function getId();

    /**
     * [getRateLimit description]
     * @return [type] [description]
     */
    public function getRateLimit();

    /**
     * @return string
     */
    public function getMethod();

    /**
     * @return string
     */
    public function getPath();

    /**
     * @return string
     */
    public function getFormat();

    /**
     * [getContent description]
     * @return [type] [description]
     */
    public function getContent();

    /**
     * [getUrl description]
     * @return [type] [description]
     */
    public function getUrl();

    /**
     * [getType description]
     * @return [type] [description]
     */
    public function getType();
    
    /**
     * @return bool
     */
    public function isPublic();

    /**
     * @return PresenterContract
     */
    public function getPresenter();

    /**
     * @return array
     */
    public function getConfig();
}