<?php

declare(strict_types=1);

use Kaveraa\ApiGouv\Adresse\Coordonnees;
use Kaveraa\ApiGouv\Exceptions\NotFoundException;
use Kaveraa\ApiGouv\Testing\Factories;
use Kaveraa\ApiGouv\Testing\FakeAdresse;
use Kaveraa\ApiGouv\Testing\FakeApiGouv;
use Kaveraa\ApiGouv\Testing\FakeEntreprises;

it('builds DTOs from defaults and overrides', function () {
    $entreprise = Factories::entreprise(['siren' => '123456782', 'nomComplet' => 'ACME']);
    $adresse = Factories::adresse(['label' => '1 rue Test 75001 Paris']);

    expect($entreprise->siren)->toBe('123456782')
        ->and($entreprise->nomComplet)->toBe('ACME')
        ->and($entreprise->siege)->not->toBeNull()
        ->and($adresse->label)->toBe('1 rue Test 75001 Paris')
        ->and($adresse->coordonnees->latitude)->toBeFloat();
});

it('finds stored companies by SIREN, SIRET and name, and records the calls', function () {
    $acme = Factories::entreprise(['siren' => '123456782', 'nomComplet' => 'ACME Corp', 'siege' => Factories::etablissement(['siret' => '12345678200010'])]);
    $fake = (new FakeEntreprises)->with($acme);

    expect($fake->parSiren('123456782'))->toBe($acme)
        ->and($fake->parSiret('12345678200010')->siret)->toBe('12345678200010')
        ->and($fake->rechercher('acme')->results)->toBe([$acme])
        ->and($fake->rechercher('nothing')->results)->toBe([])
        ->and($fake->calls)->toHaveCount(4);
});

it('derives the default head office from an overridden SIREN', function () {
    $first = Factories::entreprise(['siren' => '123456782', 'nomComplet' => 'FIRST']);
    $second = Factories::entreprise(['siren' => '987654321', 'nomComplet' => 'SECOND']);
    $fake = (new FakeEntreprises)->with($first, $second);

    expect($second->siege?->siren)->toBe('987654321')
        ->and($second->siege?->siret)->toBe('98765432100040')
        ->and($fake->parSiret($second->siege->siret))->toBe($second->siege);
});

it('keeps a given head office untouched', function () {
    $siege = Factories::etablissement(['siret' => '11111111100011']);

    expect(Factories::entreprise(['siren' => '987654321', 'siege' => $siege])->siege)->toBe($siege);
});

it('rejects a malformed SIREN or SIRET like the real client', function (string $value) {
    $fake = new FakeEntreprises;

    expect(fn () => $fake->parSiren($value))->toThrow(InvalidArgumentException::class, 'A SIREN must have 9 digits.')
        ->and(fn () => $fake->parSiret($value))->toThrow(InvalidArgumentException::class, 'A SIRET must have 14 digits.');
})->with(['', '12345', 'abcdefghi', '1234567890']);

it('throws NotFoundException for an unknown SIREN or SIRET', function () {
    $fake = new FakeEntreprises;

    expect(fn () => $fake->parSiren('123456782'))->toThrow(NotFoundException::class)
        ->and(fn () => $fake->parSiret('12345678200010'))->toThrow(NotFoundException::class);
});

it('filters stored addresses and finds the nearest one', function () {
    $paris = Factories::adresse(['label' => '1 rue Test 75001 Paris', 'coordonnees' => new Coordonnees(48.86, 2.34)]);
    $lyon = Factories::adresse(['label' => '2 rue Test 69001 Lyon', 'coordonnees' => new Coordonnees(45.76, 4.83)]);
    $fake = (new FakeAdresse)->with($paris, $lyon);

    expect($fake->rechercher('paris'))->toBe([$paris])
        ->and($fake->rechercher('rue test', 1))->toHaveCount(1)
        ->and($fake->autocompleter('lyon'))->toBe([$lyon])
        ->and($fake->geocoderInverse(45.7, 4.8))->toBe($lyon)
        ->and((new FakeAdresse)->geocoderInverse(0.0, 0.0))->toBeNull();
});

it('groups both fakes in FakeApiGouv', function () {
    $fake = new FakeApiGouv;

    expect($fake->entreprises())->toBeInstanceOf(FakeEntreprises::class)
        ->and($fake->adresse())->toBeInstanceOf(FakeAdresse::class);
});
