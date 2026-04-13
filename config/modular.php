<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sidebar cache
    |--------------------------------------------------------------------------
    | The sidebar groups are built by collecting config/sidebar.php from every
    | enabled module, filtering by user permissions, then sorting. This result
    | is cached per user to avoid repeated filesystem and permission checks.
    |
    | Set MODULAR_SIDEBAR_CACHE=false in .env to disable during development.
    */
    'sidebar' => [
        'cache'     => env('MODULAR_SIDEBAR_CACHE', true),
        'cache_ttl' => (int) env('MODULAR_SIDEBAR_TTL', 3600),
    ],

];
