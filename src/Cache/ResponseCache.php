<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Cache;

use Psr\SimpleCache\CacheInterface;

final class ResponseCache
{
    public function __construct(private readonly CacheInterface $cache) {}

    /**
     * @param  callable(): array<mixed>  $fetch
     * @return array<mixed>
     */
    public function remember(string $key, callable $fetch, int $ttl): array
    {
        $hit = $this->cache->get($key);
        if (is_array($hit)) {
            return $hit;
        }

        $value = $fetch();
        $this->cache->set($key, $value, $ttl);

        return $value;
    }
}
