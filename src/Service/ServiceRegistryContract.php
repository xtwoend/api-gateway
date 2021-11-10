<?php

namespace Xtwoend\ApiGateway\Service;

use Xtwoend\ApiGateway\Service\Service;

interface ServiceRegistryContract
{
    /**
     * Find an instance of a specified microservice
     * Returns URL (RESTful services always have URLs).
     *
     * @param $serviceId
     * @param mixed $services
     * @return string
     */
    public function resolveInstance($services): ?Service;
}
