<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Http;

use Kaveraa\ApiGouv\Exceptions\ApiException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

final class Psr18Transport implements Transport
{
    public function __construct(
        private readonly ClientInterface $client,
        private readonly RequestFactoryInterface $requests,
    ) {}

    public function get(string $url, array $query = []): Response
    {
        $query = array_filter($query, static fn ($value) => $value !== null);
        if ($query !== []) {
            $url .= '?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        $request = $this->requests->createRequest('GET', $url)->withHeader('Accept', 'application/json');

        try {
            $response = $this->client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new ApiException('Network error: '.$e->getMessage(), 0, $e);
        }

        $headers = [];
        foreach ($response->getHeaders() as $name => $values) {
            $headers[strtolower($name)] = $values[0] ?? '';
        }

        return new Response($response->getStatusCode(), (string) $response->getBody(), $headers);
    }
}
