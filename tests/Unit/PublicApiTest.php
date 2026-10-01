<?php

declare(strict_types=1);

use Kaveraa\ApiGouv\Tests\Support\PublicApi;

// Locks the public surface the backward compatibility promise covers. The Laravel and Symfony
// bridges need their frameworks, so they are covered by their own suites, not by this snapshot.

it('skips internal members but keeps the class', function () {
    $api = PublicApi::describe();

    expect($api)->toHaveKey('Kaveraa\ApiGouv\Entreprises\Entreprise')
        ->and($api['Kaveraa\ApiGouv\Entreprises\Entreprise']['methods'])->not->toHaveKey('__construct')
        ->and($api['Kaveraa\ApiGouv\Entreprises\Entreprise']['properties'])->toHaveKey('siren')
        ->and($api)->not->toHaveKey('Kaveraa\ApiGouv\Support\Payload')
        ->and($api['Kaveraa\ApiGouv\Coordonnees']['methods'])->toHaveKey('__construct');
});

it('matches the committed snapshot', function () {
    $file = dirname(__DIR__).'/fixtures/public-api.json';
    $current = PublicApi::describe();

    if (getenv('API_GOUV_UPDATE_SNAPSHOT') === '1') {
        file_put_contents($file, json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
    }

    expect(file_exists($file))->toBeTrue('No snapshot yet: run with API_GOUV_UPDATE_SNAPSHOT=1.');

    $expected = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
    $diff = PublicApi::diff($expected, $current);

    expect($diff)->toBe([], "The public API changed:\n".implode("\n", $diff)."\nIf this is intended, run with API_GOUV_UPDATE_SNAPSHOT=1 and explain it in the changelog.");
});
