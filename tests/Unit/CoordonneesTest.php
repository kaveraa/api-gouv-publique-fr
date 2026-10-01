<?php

declare(strict_types=1);

use Kaveraa\ApiGouv\Coordonnees;

it('holds a latitude and a longitude at the root namespace', function () {
    $point = new Coordonnees(49.8987, 2.2847);

    expect($point->latitude)->toBe(49.8987)
        ->and($point->longitude)->toBe(2.2847)
        ->and($point::class)->toBe('Kaveraa\ApiGouv\Coordonnees');
});
