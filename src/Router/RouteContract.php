<?php

namespace Xtwoend\ApiGateway\Router;

use Xtwoend\ApiGateway\Service\Service;
use Xtwoend\ApiGateway\Service\ServiceContract;


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
     * [getHeaders description]
     * @return [type] [description]
     */
    public function getHeaders();

    /**
     * [getContent description]
     * @return [type] [description]
     */
    public function getContent();

    /**
     * [getAction description]
     * @return [type] [description]
     */
    public function getAction();

    /**
     * [addService description]
     * @param ServiceContract $service [description]
     */
    public function addService(ServiceContract $service);

    /**
     * [getCurrentService description]
     * @return [type] [description]
     */
    public function getCurrentService(): int;

    /**
     * [setCurrentService description]
     * @param int $index [description]
     */
    public function setCurrentService(int $index): void;

    /**
     * [getService description]
     * @param  int    $index [description]
     * @return [type]        [description]
     */
    public function getService(int $index): ?Service;

    /**
     * [getServices description]
     * @return [type] [description]
     */
    public function getServices();

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
     * @return array middleware name
     */
    public function getMiddleware();

    /**
     * @return array
     */
    public function getConfig();
}