<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Tests\Support;

use Kaveraa\ApiGouv\Http\Response;
use Kaveraa\ApiGouv\Http\Transport;
use LogicException;

final class FakeTransport implements Transport
{
    /** @var list<array{0: string, 1: array<string, scalar|null>}> */
    public array $calls = [];

    /** @var list<Response> */
    private array $queue;

    public function __construct(Response ...$responses)
    {
        $this->queue = array_values($responses);
    }

    public function get(string $url, array $query = []): Response
    {
        $this->calls[] = [$url, $query];

        return array_shift($this->queue) ?? throw new LogicException('No response queued in FakeTransport.');
    }

    /** @param array<string, string> $headers */
    public static function json(string $body, int $status = 200, array $headers = []): Response
    {
        return new Response($status, $body, $headers);
    }
}
