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

use Xtwoend\ApiGateway\Service\Service;
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

        $service->hit();

        if ($service->isDown()) {
            throw new ServiceDownException("Service down");
        }

        return $service;
    }
}
