<?php

declare(strict_types=1);

namespace Drupal\croct\Tests;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\croct\CacheContextMarker;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(CacheContextMarker::class)]
#[TestDox('The Drupal cache-context marker')]
final class CacheContextMarkerTest extends TestCase
{
    #[TestDox('Marks a plain response private.')]
    public function testMarksPlainResponsePrivate(): void
    {
        $response = new Response();
        $response->setPublic();

        (new CacheContextMarker())->mark($response);

        self::assertTrue($response->headers->hasCacheControlDirective('private'));
    }

    #[TestDox('Marks a cacheable response private and adds the session cache context.')]
    public function testAddsSessionContextToCacheableResponse(): void
    {
        $metadata = $this->createMock(CacheableMetadata::class);
        $metadata->expects(self::once())
            ->method('addCacheContexts')
            ->with(['session']);

        $response = new class ($metadata) extends CacheableResponse {
            private CacheableMetadata $metadata;

            public function __construct(CacheableMetadata $metadata)
            {
                parent::__construct();

                $this->metadata = $metadata;
            }

            public function getCacheableMetadata(): CacheableMetadata
            {
                return $this->metadata;
            }
        };

        (new CacheContextMarker())->mark($response);

        self::assertTrue($response->headers->hasCacheControlDirective('private'));
    }
}
