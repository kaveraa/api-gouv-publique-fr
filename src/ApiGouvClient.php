<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv;

use Kaveraa\ApiGouv\Adresse\AdresseApi;
use Kaveraa\ApiGouv\Entreprises\EntreprisesApi;
use Kaveraa\ApiGouv\Geo\GeoApi;
use LogicException;

class ApiGouvClient
{
    // The geo client is optional so code written for 0.1 that builds this class by hand keeps working.
    public function __construct(
        private readonly EntreprisesApi $entreprises,
        private readonly AdresseApi $adresse,
        private readonly ?GeoApi $geo = null,
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
        return $this->geo ?? throw new LogicException('The Geo client is not configured.');
    }
}
