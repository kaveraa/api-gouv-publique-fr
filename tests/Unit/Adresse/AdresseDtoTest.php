<?php

declare(strict_types=1);

use Kaveraa\ApiGouv\Adresse\Adresse;
use Kaveraa\ApiGouv\Coordonnees;
use Kaveraa\ApiGouv\Exceptions\InvalidResponseException;

it('builds an Adresse from a GeoJSON feature', function () {
    $feature = json_decode(loadFixture('adresse_search.json'), true)['features'][0];

    $adresse = Adresse::fromFeature($feature);

    expect($adresse->label)->toBe($feature['properties']['label'])
        ->and($adresse->coordonnees)->toBeInstanceOf(Coordonnees::class)
        ->and($adresse->coordonnees->longitude)->toBe($feature['geometry']['coordinates'][0])
        ->and($adresse->coordonnees->latitude)->toBe($feature['geometry']['coordinates'][1]);
});

it('defaults every optional field when the feature has no properties', function () {
    $adresse = Adresse::fromFeature(['geometry' => ['coordinates' => [2.34, 48.86]]]);

    expect($adresse->id)->toBe('')
        ->and($adresse->label)->toBe('')
        ->and($adresse->numero)->toBeNull()
        ->and($adresse->score)->toBeNull()
        ->and($adresse->coordonnees->latitude)->toBe(48.86);
});

it('raises InvalidResponseException without a point', function () {
    Adresse::fromFeature(['properties' => ['label' => 'x']]);
})->throws(InvalidResponseException::class, 'An address has no coordinates.');

it('builds with the required fields only', function () {
    $adresse = new Adresse('id', 'label', new Coordonnees(48.86, 2.34));

    expect($adresse->rue)->toBeNull()->and($adresse->type)->toBeNull();
});
