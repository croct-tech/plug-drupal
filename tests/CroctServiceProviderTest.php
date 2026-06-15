<?php

declare(strict_types=1);

namespace Drupal\croct\Tests;

use Croct\Plug\LoadMode;
use Croct\Plug\Symfony\DependencyInjection\Compiler\StoryblokIntegrationPass;
use Croct\Plug\Symfony\EventListener\CroctScriptSubscriber;
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

    #[TestDox('Enables Storyblok by default and registers the decoration pass.')]
    public function testEnablesStoryblokByDefault(): void
    {
        new Settings([]);

        $container = new ContainerBuilder();

        (new CroctServiceProvider())->register($container);

        self::assertTrue($container->getParameter('croct.storyblok.enabled'));

        $passes = \array_filter(
            $container->getCompiler()->getPassConfig()->getPasses(),
            static fn (object $pass): bool => $pass instanceof StoryblokIntegrationPass,
        );

        self::assertCount(1, $passes);
    }

    #[TestDox('Allows disabling the Storyblok integration through settings.')]
    public function testAllowsDisablingStoryblok(): void
    {
        new Settings(['croct.storyblok.enabled' => false]);

        $container = new ContainerBuilder();

        (new CroctServiceProvider())->register($container);

        self::assertFalse($container->getParameter('croct.storyblok.enabled'));
    }

    #[TestDox('Wires the configured sync script mode into the script subscriber.')]
    public function testWiresSyncScriptMode(): void
    {
        new Settings(['croct.script.mode' => 'sync']);

        self::assertSame(LoadMode::SYNC, $this->alterScriptMode());
    }

    #[TestDox('Wires the configured async script mode into the script subscriber.')]
    public function testWiresAsyncScriptMode(): void
    {
        new Settings(['croct.script.mode' => 'async']);

        self::assertSame(LoadMode::ASYNC, $this->alterScriptMode());
    }

    #[TestDox('Defaults the script mode to defer.')]
    public function testDefaultsScriptMode(): void
    {
        new Settings([]);

        self::assertSame(LoadMode::DEFER, $this->alterScriptMode());
    }

    #[TestDox('Leaves the container untouched when the script subscriber is absent.')]
    public function testIgnoresMissingScriptSubscriber(): void
    {
        new Settings([]);

        $container = new ContainerBuilder();

        (new CroctServiceProvider())->alter($container);

        self::assertFalse($container->hasDefinition(CroctScriptSubscriber::class));
    }

    private function alterScriptMode(): LoadMode
    {
        $container = new ContainerBuilder();
        $container->register(CroctScriptSubscriber::class)
            ->setArguments(['manager', '/_croct/plug.js', 'head']);

        (new CroctServiceProvider())->alter($container);

        $mode = $container->getDefinition(CroctScriptSubscriber::class)->getArguments()[3] ?? null;

        self::assertInstanceOf(LoadMode::class, $mode);

        return $mode;
    }
}
