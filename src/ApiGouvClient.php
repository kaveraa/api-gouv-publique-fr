<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv;

use Kaveraa\ApiGouv\Adresse\AdresseApi;
use Kaveraa\ApiGouv\Entreprises\EntreprisesApi;

class ApiGouvClient
{
    public function __construct(
        private readonly EntreprisesApi $entreprises,
        private readonly AdresseApi $adresse,
    ) {}

    public function entreprises(): EntreprisesApi
    {
        return $this->entreprises;
    }

    public function adresse(): AdresseApi
    {
        return $this->adresse;
    }
}
