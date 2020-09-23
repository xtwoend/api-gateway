<?php

namespace Api\Gateway\Middleware;


class CacheResponse
{
	public function handle(Request $request, Closure $next)
	{
		$response = $next($request);

		$headers = $response->headers;

		return $response;
	}
}