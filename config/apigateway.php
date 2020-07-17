<?php

return [
	'cache' => false,
	'cache_lifetime' => 5 * 360,
	'table_name' => 'routes',
	'rate_limit' => 100, // limit request per menit
    'global' => [
        'timeout' => 20,
        'connect_timeout' => 40
    ],
    'logger' => [
    	'channel' => 'http',
		'except' => [
			'pin',
			'token',
			'password',
			'password_confirmation',
		],
	]
];