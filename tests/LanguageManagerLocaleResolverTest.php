<?php

declare(strict_types=1);

namespace Drupal\croct\Tests;

use Drupal\Core\Language\LanguageInterface as Language;
use Drupal\Core\Language\LanguageManagerInterface as LanguageManager;
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
        $language = $this->createMock(Language::class);
        $language->method('getId')->willReturn('fr');

        $manager = $this->createMock(LanguageManager::class);
        $manager->method('getCurrentLanguage')->willReturn($language);

        self::assertSame('fr', (new LanguageManagerLocaleResolver($manager))->getLocale());
    }
}
