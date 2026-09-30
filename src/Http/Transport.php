<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Http;

interface Transport
{
    /** @param array<string, scalar|null> $query Null values are skipped. */
    public function get(string $url, array $query = []): Response;
}
