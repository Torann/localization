<?php

use Torann\Localization\LocaleManager;

if (! function_exists('localization')) {
    /**
     * Simple localization helper.
     *
     * @return \Torann\Localization\LocaleManager
     */
    function localization()
    {
        return app(LocaleManager::class);
    }
}

if (! function_exists('localize_url')) {
    /**
     * Returns an URL adapted to locale
     *
     * @param string $locale
     * @param string $url
     * @param array  $attributes
     *
     * @return string
     */
    function localize_url($locale = null, $url = null, $attributes = [])
    {
        return localization()->getLocalizedURL($locale, $url, $attributes);
    }
}