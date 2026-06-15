<?php

declare(strict_types=1);

namespace Drupal\croct;

use Croct\Plug\LoadMode;
use Croct\Plug\Symfony\DependencyInjection\Compiler\StoryblokIntegrationPass;
use Croct\Plug\Symfony\EventListener\CroctScriptSubscriber;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\Core\Site\Settings;

/**
 * Bridges the Croct credentials from settings.php into the container.
 */
final class CroctServiceProvider implements ServiceProviderInterface, ServiceModifierInterface
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

    public function alter(ContainerBuilder $container): void
    {
        // The loader mode is an enum, which the YAML loader cannot express, so wire it here from the
        // croct.script.mode setting (defaulting to defer, like the Symfony bundle).
        if ($container->hasDefinition(CroctScriptSubscriber::class)) {
            $container->getDefinition(CroctScriptSubscriber::class)->setArgument(3, self::scriptMode());
        }
    }

    private static function scriptMode(): LoadMode
    {
        return match (Settings::get('croct.script.mode', 'defer')) {
            'sync' => LoadMode::SYNC,
            'async' => LoadMode::ASYNC,
            default => LoadMode::DEFER,
        };
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
