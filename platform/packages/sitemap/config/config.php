<?php

return [
    'use_cache' => false,
    'cache_key' => 'cms-sitemap.',
    'cache_duration' => 3600,
    'escaping' => true,
    'use_limit_size' => false,
    'max_size' => null,
    'use_styles' => true,
    'styles_location' => '/vendor/core/packages/sitemap/styles/',
    'use_gzip' => false,
    'indexnow_endpoints' => [
        'bing' => 'https://api.indexnow.org/indexnow',
        'yandex' => 'https://yandex.com/indexnow',
        'seznam' => 'https://search.seznam.cz/indexnow',
        'naver' => 'https://searchadvisor.naver.com/indexnow',
    ],
];
