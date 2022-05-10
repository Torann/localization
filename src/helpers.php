<?php

use Torann\Localization\LocaleManager;

if (! function_exists('localization')) {
    /**
     * Localization helper.
     *
     * @return LocaleManager
     */
    function localization(): LocaleManager
    {
        return app(LocaleManager::class);
    }
}

if (! function_exists('localize_url')) {
    /**
     * Returns a URL adapted to provided locale or current locale
     *
     * @param string|null $url
     * @param string|null $locale
     * @param array       $extra
     *
     * @return string
     */
    function localize_url(string $url = null, string $locale = null, array $extra = []): string
    {
        return localization()->getLocalizedURL($url, $locale, $extra);
    }
}
