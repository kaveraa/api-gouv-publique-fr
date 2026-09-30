<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Laravel;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Kaveraa\ApiGouv\Exceptions\ApiException;
use Kaveraa\ApiGouv\Http\Response;
use Kaveraa\ApiGouv\Http\Transport;

final class LaravelHttpTransport implements Transport
{
    public function __construct(
        private readonly int $timeout = 10,
        private readonly int $attempts = 3,
        private readonly int $retryDelayMs = 300,
    ) {}

    public function get(string $url, array $query = []): Response
    {
        $query = array_filter($query, static fn ($value) => $value !== null);

        try {
            // Only a 429 is retried. Other errors are returned as they are.
            $response = Http::acceptJson()
                ->timeout($this->timeout)
                ->retry(
                    $this->attempts,
                    $this->retryDelayMs,
                    static fn ($exception) => $exception instanceof RequestException && $exception->response->status() === 429,
                    throw: false,
                )
                ->get($url, $query);
        } catch (ConnectionException $e) {
            throw new ApiException('Network error: '.$e->getMessage(), 0, $e);
        }

        $headers = [];
        foreach ($response->headers() as $name => $values) {
            $headers[strtolower($name)] = $values[0] ?? '';
        }

        return new Response($response->status(), $response->body(), $headers);
    }
}
