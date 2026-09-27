<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Shortcode Caching
    |--------------------------------------------------------------------------
    |
    | This option controls the caching of shortcodes. When enabled, shortcodes
    | will be cached to improve performance. You can specify which shortcodes
    | should be cached and for how long.
    |
    */
    'cache' => [
        'enabled' => env('SHORTCODE_CACHE_ENABLED', true),
        'ttl' => [
            'default' => env('SHORTCODE_CACHE_TTL_DEFAULT', 5), // seconds
            'cacheable' => env('SHORTCODE_CACHE_TTL_CACHEABLE', 1800), // 30 minutes
        ],
        'cacheable_shortcodes' => [
            'static-block',
            'featured-posts',
            'gallery',
            'youtube-video',
            'google-map',
            'contact-form',
            'image',
            // Add more cacheable shortcodes here
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    |
    | This option controls the performance monitoring of shortcodes. When enabled,
    | the system will log warnings when shortcodes take too long to render.
    |
    */
    'performance' => [
        'log_threshold' => env('SHORTCODE_PERFORMANCE_LOG_THRESHOLD', 0.5), // seconds
    ],
];
