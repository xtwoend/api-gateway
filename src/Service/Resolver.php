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
namespace Xtwoend\ApiGateway\Service;

use Hyperf\Consul\Health;
use Hyperf\DbConnection\Db;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Consul\HealthInterface;
use Xtwoend\ApiGateway\Service\Service;
use Hyperf\ServiceGovernanceConsul\ConsulAgent;
use Xtwoend\ApiGateway\Exception\ServiceDownException;
use Hyperf\ServiceGovernance\Exception\ComponentRequiredException;

/**
 *
 */
final class Resolver implements ServiceRegistryContract
{
    /**
     * Undocumented variable
     *
     * @var [type]
     */
    protected $health;
    
    /**
     * [resolveInstance description]
     * @param  [type] $services [description]
     * @return [type]            [description]
     */
    public function resolveInstance($services): ?Service
    {
        if ($services->count() > 1) {
            $services->where('default', true)->first();
            $service = $services->first();
        } else {
            $service = $services->first();
        }

        $host = $this->getNodes($service->getName());
        $service = $service->setHost($host);
        
        $service->hit();

        // counter
        $this->putHit($service);

        return $service;
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    protected function getNodes($name)
    {
        $consulAddress = config('consul.uri', 'http://127.0.0.1:8500');
        $health = $this->createConsulHealth($consulAddress);
        $services = $health->service($name)->json();
        
        $nodes = [];
        $metadata['protocol'] = 'http';
        foreach ($services as $node) {
            $passing = true;
            $service = $node['Service'] ?? [];
            $checks = $node['Checks'] ?? [];

            if (isset($service['Meta']['Protocol']) && $metadata['protocol'] !== $service['Meta']['Protocol']) {
                // The node is invalid, if the protocol is not equal with the client's protocol.
                continue;
            }

            foreach ($checks as $check) {
                $status = $check['Status'] ?? false;
                if ($status !== 'passing') {
                    $passing = false;
                }
            }

            if ($passing) {
                $address = $service['Address'] ?? '';
                $port = (int) ($service['Port'] ?? 0);
                // @TODO Get and set the weight property.
                $address && $port && $nodes[] = ['host' => $address, 'port' => $port];
            }
        }

        if (empty($nodes)) {
            throw new ServiceDownException('No node alive.');
        }
        $key = array_rand($nodes);
        $node = $nodes[$key];

        $uri = $node['host'] . ':' . $node['port'];
        $schema = value(function () use ($node) {
            $schema = 'http';
            if (array_key_exists('schema', $node)) {
                $schema = $node['schema'];
            }
            if (! in_array($schema, ['http', 'https'])) {
                $schema = 'http';
            }
            $schema .= '://';
            return $schema;
        });
        $url = $schema . $uri;
        
        return $url;
    }   

    /**
     * simpan hit ke dalam database
     *
     * @param [type] $service
     * @return void
     */
    private function putHit($service)
    {
        Db::table('services')->where('id', $service->getId())
        ->update([
            'hit' => $service->getHit()
        ]);
    }

    protected function createConsulHealth(string $baseUri): HealthInterface
    {
        if ($this->health instanceof HealthInterface) {
            return $this->health;
        }

        if (! class_exists(Health::class)) {
            throw new ComponentRequiredException('Component of \'hyperf/consul\' is required if you want the client fetch the nodes info from consul.');
        }

        $token = config('services.drivers.consul.token', '');
        $options = [
            'base_uri' => $baseUri,
        ];

        if (! empty($token)) {
            $options['headers'] = [
                'X-Consul-Token' => $token,
            ];
        }

        return $this->health = make(Health::class, [
            'clientFactory' => function () use ($options) {
                return make(ClientFactory::class)->create($options);
            },
        ]);
    }
}
