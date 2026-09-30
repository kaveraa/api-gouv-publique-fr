<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Laravel;

use Illuminate\Support\Facades\Facade;
use Kaveraa\ApiGouv\Adresse\AdresseApi;
use Kaveraa\ApiGouv\ApiGouvClient;
use Kaveraa\ApiGouv\Entreprises\EntreprisesApi;
use Kaveraa\ApiGouv\Testing\FakeApiGouv;

/**
 * @method static EntreprisesApi entreprises()
 * @method static AdresseApi adresse()
 * @method static FakeApiGouv fake()
 *
 * @see ApiGouvClient
 */
final class ApiGouv extends Facade
{
    /** Replace the clients with in-memory fakes for the rest of the test. */
    public static function fake(): FakeApiGouv
    {
        $fake = new FakeApiGouv;
        static::swap($fake);

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return ApiGouvClient::class;
    }
}
