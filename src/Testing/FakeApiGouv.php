<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Testing;

use Kaveraa\ApiGouv\ApiGouvClient;

final class FakeApiGouv extends ApiGouvClient
{
    private readonly FakeEntreprises $fakeEntreprises;

    private readonly FakeAdresse $fakeAdresse;

    public function __construct(?FakeEntreprises $entreprises = null, ?FakeAdresse $adresse = null)
    {
        $this->fakeEntreprises = $entreprises ?? new FakeEntreprises;
        $this->fakeAdresse = $adresse ?? new FakeAdresse;

        parent::__construct($this->fakeEntreprises, $this->fakeAdresse);
    }

    public function entreprises(): FakeEntreprises
    {
        return $this->fakeEntreprises;
    }

    public function adresse(): FakeAdresse
    {
        return $this->fakeAdresse;
    }
}
