<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Http;

use JsonException;
use Kaveraa\ApiGouv\Cache\ResponseCache;
use Kaveraa\ApiGouv\Exceptions\ApiException;
use Kaveraa\ApiGouv\Exceptions\InvalidResponseException;
use Kaveraa\ApiGouv\Exceptions\NotFoundException;
use Kaveraa\ApiGouv\Exceptions\RateLimitException;

final class Requester
{
    public function __construct(
        private readonly Transport $transport,
        private readonly string $baseUrl,
        private readonly ?ResponseCache $cache = null,
        private readonly int $ttl = 3600,
    ) {}

    /**
     * @param  array<string, scalar|null>  $query
     * @return array<mixed>
     */
    public function getJson(string $path, array $query = []): array
    {
        $url = rtrim($this->baseUrl, '/').'/'.ltrim($path, '/');
        $fetch = fn (): array => $this->decode($this->transport->get($url, $query));

        if ($this->cache === null) {
            return $fetch();
        }

        // Sort so the same query in a different order shares one cache entry.
        ksort($query);

        return $this->cache->remember('api-gouv.'.sha1($url.'?'.http_build_query($query)), $fetch, $this->ttl);
    }

    /** @return array<mixed> */
    private function decode(Response $response): array
    {
        $status = $response->status;

        if ($status === 429) {
            $wait = $response->header('retry-after');

            throw new RateLimitException($wait !== null && ctype_digit($wait) ? (int) $wait : null);
        }
        if ($status === 404) {
            throw new NotFoundException('Resource not found.', 404);
        }
        if ($status >= 400) {
            throw new ApiException(sprintf('API error %d: %s', $status, $this->errorMessage($response)), $status);
        }

        try {
            $data = json_decode($response->body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidResponseException('The API returned invalid JSON.', $status, $e);
        }

        if (! is_array($data)) {
            throw new InvalidResponseException('The API returned an unexpected JSON value.', $status);
        }

        return $data;
    }

    private function errorMessage(Response $response): string
    {
        $data = json_decode($response->body, true);
        if (is_array($data)) {
            foreach (['erreur', 'message', 'error'] as $key) {
                if (isset($data[$key]) && is_string($data[$key])) {
                    return $data[$key];
                }
            }
        }

        return substr(trim($response->body), 0, 200);
    }
}
