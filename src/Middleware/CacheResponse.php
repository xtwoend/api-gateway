<?php

namespace Api\Gateway\Middleware;

use Growinc\ModGatewayCache\After;
use Growinc\ModGatewayCache\Before;


class CacheResponse
{
	private $config = [];

	public function __construct()
	{
		$this->config['cache'] = [
			'adapter' => 'apc',
			'options' => ['ttl' => 3600],
		];
	}

	public function handle(Request $request, Closure $next)
	{
		$response = $next($request);

		return $response;
	}
}