<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Tests\Symfony;

use Kaveraa\ApiGouv\Adresse\AdresseApi;
use Kaveraa\ApiGouv\ApiGouvClient;
use Kaveraa\ApiGouv\Entreprises\EntreprisesApi;
use Kaveraa\ApiGouv\Geo\GeoApi;

/** An application service that receives the clients by autowiring. */
final class AutowiredConsumer
{
    public function __construct(
        public readonly EntreprisesApi $entreprises,
        public readonly AdresseApi $adresse,
        public readonly GeoApi $geo,
        public readonly ApiGouvClient $client,
    ) {}
}
