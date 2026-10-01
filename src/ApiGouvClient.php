<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv;

use Kaveraa\ApiGouv\Adresse\AdresseApi;
use Kaveraa\ApiGouv\Entreprises\EntreprisesApi;
use Kaveraa\ApiGouv\Geo\GeoApi;

/** Entry point holding the three API clients. Extending it is outside the backward compatibility promise. */
class ApiGouvClient
{
    public function __construct(
        private readonly EntreprisesApi $entreprises,
        private readonly AdresseApi $adresse,
        private readonly GeoApi $geo,
    ) {}

    public function entreprises(): EntreprisesApi
    {
        return $this->entreprises;
    }

    public function adresse(): AdresseApi
    {
        return $this->adresse;
    }

    public function geo(): GeoApi
    {
        return $this->geo;
    }
}
