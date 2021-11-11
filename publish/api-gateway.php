<?php

return [
    'cache' => false,
    'middleware' => [
        'auth' => null,
        'project' => null
    ],
    'http' => [
        'max_connection' => 50,
        'retries' => 1,
        'delay' => 5
    ]
];