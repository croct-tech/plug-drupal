<?php

declare(strict_types=1);

namespace Drupal\croct\Tests;

use Drupal\Component\Datetime\TimeInterface as Time;
use Drupal\Core\Cache\CacheBackendInterface as CacheBackend;
use Drupal\croct\PsrCacheAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

#[CoversClass(PsrCacheAdapter::class)]
#[TestDox('The PSR-16 Drupal cache adapter')]
final class PsrCacheAdapterTest extends TestCase
{
    #[TestDox('Returns the stored data on a cache hit.')]
    public function testReturnsDataOnHit(): void
    {
        $cache = $this->createMock(CacheBackend::class);
        $cache->method('get')->with('key')->willReturn((object) ['data' => 'value']);

        self::assertSame('value', $this->adapter($cache)->get('key'));
    }

    #[TestDox('Returns the default on a cache miss.')]
    public function testReturnsDefaultOnMiss(): void
    {
        $cache = $this->createMock(CacheBackend::class);
        $cache->method('get')->willReturn(false);

        self::assertSame('fallback', $this->adapter($cache)->get('key', 'fallback'));
    }

    #[TestDox('Stores a value with a relative TTL as an absolute expiration.')]
    public function testStoresWithIntegerTtl(): void
    {
        $cache = $this->createMock(CacheBackend::class);
        $cache->expects(self::once())->method('set')->with('key', 'value', 1000 + 3600);

        self::assertTrue($this->adapter($cache)->set('key', 'value', 3600));
    }

    #[TestDox('Stores a value without a TTL permanently.')]
    public function testStoresPermanentlyWithoutTtl(): void
    {
        $cache = $this->createMock(CacheBackend::class);
        $cache->expects(self::once())
            ->method('set')
            ->with('key', 'value', CacheBackend::CACHE_PERMANENT);

        self::assertTrue($this->adapter($cache)->set('key', 'value'));
    }

    #[TestDox('Resolves a DateInterval TTL against the current time.')]
    public function testStoresWithIntervalTtl(): void
    {
        $cache = $this->createMock(CacheBackend::class);
        $cache->expects(self::once())->method('set')->with('key', 'value', 1000 + 3600);

        self::assertTrue($this->adapter($cache)->set('key', 'value', new \DateInterval('PT1H')));
    }

    #[TestDox('Falls back to permanent caching when the current time is unusable.')]
    public function testFallsBackToPermanentOnUnusableTime(): void
    {
        $cache = $this->createMock(CacheBackend::class);
        $cache->expects(self::once())
            ->method('set')
            ->with('key', 'value', CacheBackend::CACHE_PERMANENT);

        // The time service contract declares no return type; a non-numeric value makes the
        // '@'-prefixed timestamp unparsable, so the adapter degrades to permanent caching.
        $time = $this->createMock(Time::class);
        $time->method('getCurrentTime')->willReturn('not-a-timestamp');

        self::assertTrue(
            (new PsrCacheAdapter($cache, $time))->set('key', 'value', new \DateInterval('PT1H')),
        );
    }

    #[TestDox('Deletes a single entry.')]
    public function testDeletesEntry(): void
    {
        $cache = $this->createMock(CacheBackend::class);
        $cache->expects(self::once())->method('delete')->with('key');

        self::assertTrue($this->adapter($cache)->delete('key'));
    }

    #[TestDox('Clears every entry.')]
    public function testClearsEverything(): void
    {
        $cache = $this->createMock(CacheBackend::class);
        $cache->expects(self::once())->method('deleteAll');

        self::assertTrue($this->adapter($cache)->clear());
    }

    #[TestDox('Reports presence based on the backend.')]
    public function testReportsPresence(): void
    {
        $cache = $this->createMock(CacheBackend::class);
        $cache->method('get')->willReturnCallback(
            static fn (string $cid): object|false => $cid === 'present' ? (object) ['data' => 1] : false,
        );

        $adapter = $this->adapter($cache);

        self::assertTrue($adapter->has('present'));
        self::assertFalse($adapter->has('absent'));
    }

    #[TestDox('Reads multiple entries, falling back to the default on a miss.')]
    public function testReadsMultiple(): void
    {
        $cache = $this->createMock(CacheBackend::class);
        $cache->method('get')->willReturnCallback(
            static fn (string $cid): object|false => $cid === 'a' ? (object) ['data' => 'A'] : false,
        );

        self::assertSame(
            ['a' => 'A', 'b' => 'x'],
            $this->adapter($cache)->getMultiple(['a', 'b'], 'x'),
        );
    }

    #[TestDox('Writes multiple entries.')]
    public function testWritesMultiple(): void
    {
        $cache = $this->createMock(CacheBackend::class);
        $cache->expects(self::exactly(2))->method('set');

        self::assertTrue($this->adapter($cache)->setMultiple(['a' => 1, 'b' => 2], 3600));
    }

    #[TestDox('Deletes multiple entries.')]
    public function testDeletesMultiple(): void
    {
        $cache = $this->createMock(CacheBackend::class);
        $cache->expects(self::exactly(2))->method('delete');

        self::assertTrue($this->adapter($cache)->deleteMultiple(['a', 'b']));
    }

    private function adapter(CacheBackend $cache): PsrCacheAdapter
    {
        $time = $this->createMock(Time::class);
        $time->method('getCurrentTime')->willReturn(1000);

        return new PsrCacheAdapter($cache, $time);
    }
}
