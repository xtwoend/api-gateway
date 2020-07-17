<?php

namespace Api\Gateway\Services;

/**
 * Class RandomDNSRegistry
 * @package Api\Gateway\Services
 * 
 * @author lijiebin
 */
class RandomDNSRegistry implements ServiceRegistryContract
{
    /**
     * @param string $serviceId
     * @return string
     */
    public function resolveInstance($serviceId)
    {
        $config = config('gateway');
        
        $hosts = $config['services'][$serviceId]['hostname'];
        
        if (is_array($hosts) && $hosts) {
            $hostname = $hosts[array_rand($hosts)];
        } else {
            $hostname = $hosts ?? "{$serviceId}.{$config['global']['domain']}";
        }

        return "http://{$hostname}";
    }
}
