<?php

use Illuminate\Support\Facades\Cache;

// $router->post('/auth', '\Dusterio\LumenPassport\Http\Controllers\AccessTokenController@issueToken');

$router->get('cache', function(){

	
	if(Cache::has('api-hit')){
		$hit = Cache::get('api-hit');
		Cache::put('api-hit', $hit + 1 );
	}else{
		$hit = 0;
	}

	return response(['data' => 'me', 'hit' => $hit])
		->withHeaders([
		    'Content-Type' => 'application/json',
		    'Cache-Control' => 'max-age=100, public'
		]);
});