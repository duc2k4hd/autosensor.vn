<?php

return [
    'cache' => [
        'enabled' => env('SITEMAP_CACHE_ENABLED', true),
        'ttl' => env('SITEMAP_CACHE_TTL', 3600),
        'prefix' => 'sitemap:',
    ],
];
