<?php

return [
	'prefix' => '/api',
	'trusted_ips' => [
		'10.7.0.0/16', // Docker Cloud
        '103.21.244.0/22', // Cloud Flare
        '103.22.200.0/22',
        '103.31.4.0/22',
        '104.16.0.0/12',
        '108.162.192.0/18',
        '131.0.72.0/22',
        '141.101.64.0/18',
        '162.158.0.0/15',
        '172.64.0.0/13',
        '173.245.48.0/20',
        '188.114.96.0/20',
        '190.93.240.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '199.27.128.0/21',
        '172.31.0.0/16', // Rancher
        '10.42.0.0/16' // Rancher
	],
    'cache_response' => env('API_RESPONSE_CACHE', false),
    'route' => [
    	'cache' => env('API_ROUTE_CACHE', false),
    	'cache_lifetime' => 5 * 360
    ],
    'global' => [
        'timeout' => 20,
        'connect_timeout' => 20
    ],
    'circuit_breaker' => [
        'time_window' => 30, // pengecekan dalam kurun waktu (seconds) dalam ambang batas
        'failure_rate_threshold' => 30, // Ambang batas tingkat kegagalan dalam persentase yang mengubah status CircuitBreaker menjadi `OPEN`.
        'interval_to_halfopen' => 10, // Interval (detik) untuk mengubah status CircuitBreaker dari `OPEN` menjadi` HALF_OPEN`.
        'minimum_request' => 10, // Jumlah minimum permintaan untuk mendeteksi kegagalan.
    ],
	'logger' => [
        'enable' => true,
		'channel' => 'http',
		'except' => [
			'pin',
			'token',
			'password',
			'password_confirmation',
		],
	]
];