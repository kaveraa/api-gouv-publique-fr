<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Testing;

use Kaveraa\ApiGouv\ApiGouvClient;

final class FakeApiGouv extends ApiGouvClient
{
    private readonly FakeEntreprises $fakeEntreprises;

    private readonly FakeAdresse $fakeAdresse;

    private readonly FakeGeo $fakeGeo;

    public function __construct(?FakeEntreprises $entreprises = null, ?FakeAdresse $adresse = null, ?FakeGeo $geo = null)
    {
        $this->fakeEntreprises = $entreprises ?? new FakeEntreprises;
        $this->fakeAdresse = $adresse ?? new FakeAdresse;
        $this->fakeGeo = $geo ?? new FakeGeo;

        parent::__construct($this->fakeEntreprises, $this->fakeAdresse, $this->fakeGeo);
    }

    public function entreprises(): FakeEntreprises
    {
        return $this->fakeEntreprises;
    }

    public function adresse(): FakeAdresse
    {
        return $this->fakeAdresse;
    }

    public function geo(): FakeGeo
    {
        return $this->fakeGeo;
    }
}
