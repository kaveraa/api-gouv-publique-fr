<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Tests\Symfony;

use Symfony\Component\HttpClient\Response\MockResponse;

/** Response factory for MockHttpClient: replays queued fixtures and records the requested URLs. */
final class MockResponses
{
    /** @var list<MockResponse> */
    public static array $queue = [];

    /** @var list<string> */
    public static array $urls = [];

    /** @param array<string, mixed> $options */
    public function __invoke(string $method, string $url, array $options): MockResponse
    {
        self::$urls[] = $url;

        return array_shift(self::$queue) ?? new MockResponse('[]', ['http_code' => 200]);
    }

    public static function reset(): void
    {
        self::$queue = [];
        self::$urls = [];
    }
}
