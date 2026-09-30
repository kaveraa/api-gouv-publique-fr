<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Laravel;

use Illuminate\Support\Facades\Facade;
use Kaveraa\ApiGouv\Adresse\AdresseApi;
use Kaveraa\ApiGouv\ApiGouvClient;
use Kaveraa\ApiGouv\Entreprises\EntreprisesApi;

/**
 * @method static EntreprisesApi entreprises()
 * @method static AdresseApi adresse()
 *
 * @see ApiGouvClient
 */
final class ApiGouv extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ApiGouvClient::class;
    }
}
