<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Exceptions;

final class RateLimitException extends ApiException
{
    public function __construct(public readonly ?int $retryAfter = null)
    {
        parent::__construct('Rate limit reached. Try again later.', 429);
    }
}
