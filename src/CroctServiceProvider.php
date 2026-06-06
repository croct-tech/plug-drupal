<?php

declare(strict_types=1);

namespace Drupal\croct;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\Core\Site\Settings;

/**
 * Bridges the Croct credentials from settings.php into the container.
 */
final class CroctServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $container): void
    {
        $container->setParameter('croct.app_id', self::setting('croct.app_id'));
        $container->setParameter('croct.api_key', self::setting('croct.api_key'));
    }

    private static function setting(string $name): string
    {
        $value = Settings::get($name, '');

        return \is_string($value) ? $value : '';
    }
}
