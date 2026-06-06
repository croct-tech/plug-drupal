<?php

declare(strict_types=1);

namespace Drupal\croct;

use Croct\Plug\Symfony\PersonalizationMarker;
use Drupal\Core\Cache\CacheableResponseInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Marks the response private and records the session cache context.
 *
 * The private flag keeps shared and page caches from storing it, while the cache context makes
 * Drupal's render and dynamic page caches vary the entry per visitor.
 */
final class CacheContextMarker implements PersonalizationMarker
{
    public function mark(Response $response): void
    {
        $response->setPrivate();

        if ($response instanceof CacheableResponseInterface) {
            $response->getCacheableMetadata()->addCacheContexts(['session']);
        }
    }
}
