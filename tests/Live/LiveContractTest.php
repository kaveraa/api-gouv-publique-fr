<?php

declare(strict_types=1);

use Kaveraa\ApiGouv\Adresse\AdresseClient;
use Kaveraa\ApiGouv\Entreprises\EntreprisesClient;
use Kaveraa\ApiGouv\Exceptions\RateLimitException;
use Kaveraa\ApiGouv\Geo\GeoClient;
use Kaveraa\ApiGouv\Http\Psr18Transport;
use Kaveraa\ApiGouv\Http\Requester;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

// These tests call the real APIs. They are not part of the default run.
// Run them with: php vendor/bin/pest tests/Live

function liveTransport(): Psr18Transport
{
    $client = new class implements ClientInterface
    {
        public function sendRequest(RequestInterface $request): ResponseInterface
        {
            $context = stream_context_create(['http' => ['method' => 'GET', 'header' => "Accept: application/json\r\nUser-Agent: api-gouv-publique-fr-live-tests\r\n", 'ignore_errors' => true, 'timeout' => 15]]);
            $body = @file_get_contents((string) $request->getUri(), false, $context);
            preg_match('#HTTP/\S+\s+(\d{3})#', $http_response_header[0] ?? '', $m);

            return new Response((int) ($m[1] ?? 0), [], $body === false ? '' : $body);
        }
    };

    return new Psr18Transport($client, new Psr17Factory);
}

/**
 * GitHub runners share their egress IPs, so recherche-entreprises (7 req/s per IP)
 * often answers 429, sometimes for the whole run. Retry, then skip: a persistent 429
 * means the API is alive but throttled, not that its contract changed.
 *
 * @template T
 *
 * @param  callable(): T  $call
 * @return T
 */
function withRateLimitRetry(callable $call, int $attempts = 3): mixed
{
    for ($i = 1; ; $i++) {
        try {
            return $call();
        } catch (RateLimitException $e) {
            if ($i >= $attempts) {
                test()->markTestSkipped('Rate limited by the API after '.$attempts.' attempts (shared runner IP).');
            }
            sleep($e->retryAfter ?? 5);
        }
    }
}

it('still finds a company by SIREN', function () {
    $client = new EntreprisesClient(new Requester(liveTransport(), 'https://recherche-entreprises.api.gouv.fr'));

    expect(withRateLimitRetry(fn () => $client->parSiren('812487973'))->siren)->toBe('812487973');
});

it('still finds an address', function () {
    $client = new AdresseClient(new Requester(liveTransport(), 'https://data.geopf.fr/geocodage'));

    expect($client->rechercher('8 bd du port amiens', 1))->not->toBeEmpty();
});

it('still reverse geocodes a point', function () {
    $client = new AdresseClient(new Requester(liveTransport(), 'https://data.geopf.fr/geocodage'));

    expect($client->geocoderInverse(49.897442, 2.290084)?->commune)->toBe('Amiens');
});

it('still finds a commune by INSEE code', function () {
    $client = new GeoClient(new Requester(liveTransport(), 'https://geo.api.gouv.fr'));

    expect($client->commune('80021')->nom)->toBe('Amiens');
});

it('still lists the communes of a postal code', function () {
    $client = new GeoClient(new Requester(liveTransport(), 'https://geo.api.gouv.fr'));

    expect($client->communesParCodePostal('80300'))->not->toBeEmpty();
});

it('still finds a region', function () {
    $client = new GeoClient(new Requester(liveTransport(), 'https://geo.api.gouv.fr'));

    expect($client->region('32')->nom)->toBe('Hauts-de-France');
});
