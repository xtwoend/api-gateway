<?php

namespace Xtwoend\ApiGateway\Http;

use Xtwoend\ApiGateway\Router\RouteContract;


interface HttpClientInterface
{
    public function setHeaders(array $headers);
    public function setContentType($contentType);
    public function setContentSize($contentSize);
    public function setMultipartData(array $form);
    public function getMultipartData();
    public function getHeaders();
    public function setBody($body);
    public function getBody();
    public function setFiles($files);
    public function request(RouteContract $route, $parametersJar);
}