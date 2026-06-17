<?php

declare(strict_types=1);

namespace Drupal\croct;

use Drupal\Component\Datetime\TimeInterface as Time;
use Drupal\Core\Cache\CacheBackendInterface as CacheBackend;
use Psr\SimpleCache\CacheInterface as Cache;

/**
 * Adapts a Drupal cache backend to the PSR-16 interface.
 */
final class PsrCacheAdapter implements Cache
{
    private CacheBackend $cache;

    private Time $time;

    public function __construct(CacheBackend $cache, Time $time)
    {
        $this->cache = $cache;
        $this->time = $time;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $item = $this->cache->get($key);

        if ($item === false) {
            return $default;
        }

        // Drupal cache objects expose the stored value through their `data` property.
        $fields = (array) $item;

        return $fields['data'] ?? $default;
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $this->cache->set($key, $value, $this->expiration($ttl));

        return true;
    }

    public function delete(string $key): bool
    {
        $this->cache->delete($key);

        return true;
    }

    public function clear(): bool
    {
        $this->cache->deleteAll();

        return true;
    }

    /**
     * @param iterable<string> $keys
     *
     * @return array<string, mixed>
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->get($key, $default);
        }

        return $values;
    }

    /**
     * @param iterable<mixed, mixed> $values
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            if (\is_string($key) || \is_int($key)) {
                $this->set((string) $key, $value, $ttl);
            }
        }

        return true;
    }

    /**
     * @param iterable<string> $keys
     */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        return $this->cache->get($key) !== false;
    }

    private function expiration(null|int|\DateInterval $ttl): int
    {
        if ($ttl === null) {
            return CacheBackend::CACHE_PERMANENT;
        }

        $now = $this->time->getCurrentTime();

        if (!$ttl instanceof \DateInterval) {
            return $now + $ttl;
        }

        try {
            return (new \DateTimeImmutable('@' . $now))->add($ttl)->getTimestamp();
        } catch (\Throwable) {
            return CacheBackend::CACHE_PERMANENT;
        }
    }
}
