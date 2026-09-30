# Errors, cache and rate limit

## Exceptions

| Situation | Exception | Notes |
| --- | --- | --- |
| Network error (no answer, timeout) | `ApiException` | Code is 0. The original error is the previous exception. |
| HTTP 429 (too many requests) | `RateLimitException` | Code is 429. Has `$retryAfter`. |
| HTTP 404, or unknown SIREN or SIRET | `NotFoundException` | Code is 404. |
| Other HTTP status 400 or more | `ApiException` | Code is the HTTP status. The message has the API error text. |
| Answer is not valid JSON | `InvalidResponseException` | The body is not JSON, or has a missing key such as the SIREN. |
| Bad input (empty text, wrong page size, wrong SIREN length) | `InvalidArgumentException` | A standard PHP exception. Thrown before any call. |

`RateLimitException`, `NotFoundException` and `InvalidResponseException` all extend `ApiException`. Catch `ApiException` to catch every API problem.

```php
use Kaveraa\ApiGouv\Exceptions\ApiException;
use Kaveraa\ApiGouv\Exceptions\NotFoundException;

try {
    $company = $api->entreprises()->parSiren('812487973');
} catch (NotFoundException) {
    // this company does not exist
} catch (ApiException $e) {
    // any other API problem
    logger()->warning($e->getMessage());
}
```

The exceptions are in the namespace `Kaveraa\ApiGouv\Exceptions`.

## Rate limit

The APIs limit the number of calls. When you go over the limit, the API answers HTTP 429. The package throws `RateLimitException`. If the API sends a `Retry-After` header with a number of seconds, you can read it:

```php
use Kaveraa\ApiGouv\Exceptions\RateLimitException;

try {
    $api->entreprises()->rechercher('octo');
} catch (RateLimitException $e) {
    $seconds = $e->retryAfter ?? 1;   // int or null
    sleep($seconds);
}
```

### Retry in Laravel

The Laravel transport retries by itself, and only for HTTP 429. Other errors are not retried. Two config keys control it:

- `attempts`: total number of tries (default 3).
- `retry_delay_ms`: pause between tries (default 300).

If the last try is still a 429, `RateLimitException` is thrown.

### Retry in plain PHP

The plain PHP transport does not retry. Catch `RateLimitException` and retry yourself, or use a PSR-18 client that has a retry feature.

## Cache

The cache saves calls and speeds up your code. It is off by default.

- Only successful answers are cached. Errors are never stored.
- The key is built from the URL and the query. The order of the query parameters does not matter.
- The cache time is set per API: 3600 seconds for companies, 86400 seconds for addresses and 86400 seconds for the Geo API (Laravel defaults).
- An empty search result is cached like any successful answer. A company created recently keeps returning "not found" until `cache_ttl` expires. Keep `cache_ttl` short if you look up new companies, or leave the cache off (default).

In Laravel, set `cache.enabled` to `true`. See [Laravel](laravel.md). In plain PHP, pass a `ResponseCache` to `Requester`. See [Plain PHP](plain-php.md).
