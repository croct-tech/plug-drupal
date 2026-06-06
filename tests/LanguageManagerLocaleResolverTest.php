<?php

declare(strict_types=1);

namespace Drupal\croct\Tests;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\croct\LanguageManagerLocaleResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

#[CoversClass(LanguageManagerLocaleResolver::class)]
#[TestDox('The Drupal language-manager locale resolver')]
final class LanguageManagerLocaleResolverTest extends TestCase
{
    #[TestDox('Returns the id of the negotiated current language.')]
    public function testReturnsCurrentLanguageId(): void
    {
        $language = $this->createMock(LanguageInterface::class);
        $language->method('getId')->willReturn('fr');

        $manager = $this->createMock(LanguageManagerInterface::class);
        $manager->method('getCurrentLanguage')->willReturn($language);

        self::assertSame('fr', (new LanguageManagerLocaleResolver($manager))->getLocale());
    }
}
