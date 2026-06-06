<?php

declare(strict_types=1);

namespace Drupal\croct;

use Croct\Plug\Symfony\DependencyInjection\Compiler\StoryblokIntegrationPass;
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

        // When the Storyblok Stories API service is present, the compiler pass decorates it with the
        // Croct decorator, exactly as the Symfony bundle does. No manual decoration is required.
        $container->setParameter('croct.storyblok.enabled', self::storyblokEnabled());
        $container->addCompilerPass(new StoryblokIntegrationPass());
    }

    private static function setting(string $name): string
    {
        $value = Settings::get($name, '');

        return \is_string($value) ? $value : '';
    }

    private static function storyblokEnabled(): bool
    {
        return Settings::get('croct.storyblok.enabled', true) !== false;
    }
}
