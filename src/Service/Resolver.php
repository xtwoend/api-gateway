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

use Hyperf\DbConnection\Db;
use Xtwoend\ApiGateway\Service\Service;
use Hyperf\ServiceGovernance\DriverManager;
use Xtwoend\ApiGateway\Exception\ServiceDownException;

/**
 *
 */
final class Resolver implements ServiceRegistryContract
{
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

        $host = $this->getNodes($service->name);
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
        $governanceManager = make(DriverManager::class);
        $consulAddress = config('consul.uri', 'http://127.0.0.1:8500');
        $nodes = $governanceManager->getNodes($consulAddress, $name, ['protocol' => 'http']);
        if (empty($nodes)) {
            throw new ServiceDownException('No node alive.');
        }
        $key = array_rand($nodes);
        $node = $nodes[$key];

        $uri = $node['host'] . ':' . $node['port'];
        $schema = value(function () use ($node) {
            $schema = 'http';
            if (property_exists($node, 'schema')) {
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
}
