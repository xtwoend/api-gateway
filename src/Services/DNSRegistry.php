<?php

namespace Api\Gateway\Services;

/**
 * Class DNSRegistry
 * @package Api\Gateway\Services
 */
class DNSRegistry implements ServiceRegistryContract
{
    /**
     * @param string $serviceId
     * @return string
     */
    public function resolveInstance($serviceId)
    {
        $config = config('gateway');

        // If service doesn't have a specific URL, simply append global domain to service name
        $hostname = $config['services'][$serviceId]['hostname'] ?? $serviceId . '.' . $config['global']['domain'];

        return 'http://' .  $hostname;
    }
}