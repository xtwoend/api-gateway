<?php

namespace Api\Gateway\Services;

/**
 * Interface ServiceRegistryContract
 * @package Api\Gateway\Services
 */
interface ServiceRegistryContract
{
    /**
     * Find an instance of a specified microservice
     * Returns URL (RESTful services always have URLs)
     *
     * @param $serviceId
     * @return string
     */
    public function resolveInstance($serviceId);
}