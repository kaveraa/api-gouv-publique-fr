<?php

declare(strict_types=1);

return [
    // Seconds to wait for an API answer.
    'timeout' => 10,

    // Total tries when the API answers 429 (rate limit), and the pause between tries.
    'attempts' => 3,
    'retry_delay_ms' => 300,

    // Caching is off by default. Set "store" to a cache store name, or null for the default store.
    'cache' => [
        'enabled' => false,
        'store' => null,
    ],

    'entreprises' => [
        'base_url' => 'https://recherche-entreprises.api.gouv.fr',
        'cache_ttl' => 3600,
    ],

    'adresse' => [
        'base_url' => 'https://data.geopf.fr/geocodage',
        'cache_ttl' => 86400,
    ],
];
