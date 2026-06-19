<?php

declare(strict_types=1);

namespace Drupal\croct;

use Croct\Plug\CroctScript;
use Croct\Plug\LoadMode;
use Croct\Plug\Symfony\DependencyInjection\Compiler\StoryblokIntegrationPass;
use Croct\Plug\Symfony\EventListener\CroctScriptSubscriber;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface as ServiceModifier;
use Drupal\Core\DependencyInjection\ServiceProviderInterface as ServiceProvider;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Bridges the Croct credentials from settings.php into the container.
 */
final class CroctServiceProvider implements ServiceProvider, ServiceModifier
{
    public function register(ContainerBuilder $container): void
    {
        $container->setParameter('croct.app_id', self::setting('croct.app_id'));
        $container->setParameter('croct.api_key', self::setting('croct.api_key'));

        // Forwards a debug flag to the browser SDK. Off by default; enable it in settings.php
        // (where Drupal's other development toggles live) with $settings['croct.debug'] = true.
        $container->setParameter('croct.debug', self::debug());

        // The SDK loader is served first-party from croct.script.url through CroctScriptProvider;
        // default to the CDN loader, allowing an override (e.g. a pinned version) via settings.
        $container->setParameter('croct.script.url', self::scriptUrl());

        // When the Storyblok Stories API service is present, the compiler pass decorates it with the
        // Croct decorator, exactly as the Symfony bundle does. No manual decoration is required.
        $container->setParameter('croct.storyblok.enabled', self::storyblokEnabled());
        $container->addCompilerPass(new StoryblokIntegrationPass());
    }

    public function alter(ContainerBuilder $container): void
    {
        // The loader mode is an enum, which neither the YAML loader can express nor Drupal's
        // container dumper can serialise (it rejects objects in the dumped container). Wire it as an
        // inline service built at runtime from the backed-enum value via LoadMode::from, defaulting
        // to defer like the Symfony bundle.
        if ($container->hasDefinition(CroctScriptSubscriber::class)) {
            $mode = (new Definition(LoadMode::class))
                ->setFactory([LoadMode::class, 'from'])
                ->setArguments([self::scriptMode()->value]);

            $container->getDefinition(CroctScriptSubscriber::class)->setArgument(3, $mode);
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

    private static function scriptUrl(): string
    {
        $value = Settings::get('croct.script.url', CroctScript::DEFAULT_SCRIPT_URL);

        return \is_string($value) && $value !== '' ? $value : CroctScript::DEFAULT_SCRIPT_URL;
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

    private static function debug(): bool
    {
        return Settings::get('croct.debug', false) === true;
    }
}
