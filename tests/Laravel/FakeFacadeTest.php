<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Kaveraa\ApiGouv\Entreprises\EntreprisesApi;
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
