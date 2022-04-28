<?php

return [
    'cache' => false,
    'cache_lifetime' => 9000,
    'middleware' => [
        'user' => null,
        'client' => null,
        'project' => null
    ],
    'global' => [
        'middleware' => [
            'after' => [
                // 
            ],
            'before' => [
                // 
            ]
        ]
    ],
    'http' => [
        'max_connection' => 50,
        'retries' => 1,
        'delay' => 5
    ],
    'driver' => 'consul'
];