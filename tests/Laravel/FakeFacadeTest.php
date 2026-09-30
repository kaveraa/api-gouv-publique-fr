<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Kaveraa\ApiGouv\Entreprises\EntreprisesApi;
use Kaveraa\ApiGouv\Geo\GeoApi;
use Kaveraa\ApiGouv\Laravel\ApiGouv;
use Kaveraa\ApiGouv\Testing\Factories;

it('replaces the clients for the facade and for injected interfaces', function () {
    Http::fake();
    $fake = ApiGouv::fake();
    $fake->entreprises()->with(Factories::entreprise(['siren' => '123456782', 'nomComplet' => 'ACME']));

    expect(ApiGouv::entreprises()->parSiren('123456782')->nomComplet)->toBe('ACME')
        ->and(app(EntreprisesApi::class)->parSiren('123456782')->nomComplet)->toBe('ACME');

    Http::assertNothingSent();
});

it('replaces the geo client for the facade and for the injected interface', function () {
    Http::fake();
    ApiGouv::fake()->geo()->with(Factories::commune());

    expect(ApiGouv::geo()->commune('80021')->nom)->toBe('Amiens')
        ->and(app(GeoApi::class)->commune('80021')->nom)->toBe('Amiens');

    Http::assertNothingSent();
});
