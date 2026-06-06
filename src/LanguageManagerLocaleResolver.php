<?php

declare(strict_types=1);

namespace Drupal\croct;

use Croct\Plug\LocaleResolver;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Resolves the locale from Drupal's negotiated current language.
 *
 * This follows Drupal's language negotiation (URL prefix, user preference, admin selection) rather
 * than the raw Accept-Language header, so Croct personalizes for the language Drupal is rendering.
 */
final class LanguageManagerLocaleResolver implements LocaleResolver
{
    private LanguageManagerInterface $languageManager;

    public function __construct(LanguageManagerInterface $languageManager)
    {
        $this->languageManager = $languageManager;
    }

    public function getLocale(): string
    {
        return $this->languageManager->getCurrentLanguage()->getId();
    }
}
