<?php


namespace Api\Gateway\Services;

use Api\Gateway\Exceptions\ServiceDownException;

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
	public function resolveInstance($services): ?string
	{
		$service = $services->first();
		$service->hit();
		
		if($service->isDown())
			throw new ServiceDownException("Service down");
			
		return $service->getUrl();
	}
}