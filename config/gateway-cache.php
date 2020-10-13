<?php

return [
    'storage' => [
        // config start
        // storage dengan filesystem
        'adapter' => [
            'name' => env('API_RESPONSE_CACHE_ADAPTER','filesystem'),
            // 'options' => [
            //     'cache_dir' => storage_path('app/cache')
            // ]
        ],
        'plugins' => [
            'exception_handler' => [
                'throw_exceptions' => false
            ],
            'Serializer'
        ]
        // config end
    ]
];