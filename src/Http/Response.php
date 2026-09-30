<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Http;

final readonly class Response
{
    /** @param array<string, string> $headers Header names in lower case. */
    public function __construct(
        public int $status,
        public string $body,
        public array $headers = [],
    ) {}

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }
}
