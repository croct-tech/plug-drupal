<?php

declare(strict_types=1);

namespace Drupal\croct\Tests;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Site\Settings;
use Drupal\croct\CroctServiceProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

#[CoversClass(CroctServiceProvider::class)]
#[TestDox('The Drupal service provider')]
final class CroctServiceProviderTest extends TestCase
{
    #[TestDox('Bridges the credentials from settings into container parameters.')]
    public function testBridgesCredentialsFromSettings(): void
    {
        // A non-string value falls back to an empty string.
        new Settings([
            'croct.app_id' => 'app-123',
            'croct.api_key' => 42,
        ]);

        $container = new ContainerBuilder();

        (new CroctServiceProvider())->register($container);

        self::assertSame('app-123', $container->getParameter('croct.app_id'));
        self::assertSame('', $container->getParameter('croct.api_key'));
    }
}
