<?php

declare(strict_types=1);

use Kaveraa\ApiGouv\Cache\ResponseCache;
use Kaveraa\ApiGouv\Tests\Support\ArrayCache;

it('ignores a cache value that is not an array and fetches again', function () {
    $store = new ArrayCache;
    $store->set('k', 'corrupt');
    $calls = 0;

    $value = (new ResponseCache($store))->remember('k', function () use (&$calls) {
        $calls++;

        return ['fresh' => true];
    }, 60);

    expect($value)->toBe(['fresh' => true])
        ->and($calls)->toBe(1)
        ->and($store->get('k'))->toBe(['fresh' => true]);
});
