<?php

return [
    'cache' => false,
    'cache_lifetime' => 9000,
    'middleware' => [
        'auth' => null,
        'basic' => null,
        'client' => null,
        'project' => null
    ],
    'http' => [
        'max_connection' => 50,
        'retries' => 1,
        'delay' => 5
    ]
];