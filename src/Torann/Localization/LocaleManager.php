<?php

namespace Torann\Localization;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Torann\Localization\Exceptions\UnsupportedLocaleException;
use Torann\Localization\Exceptions\SupportedLocalesNotDefined;

class LocaleManager
{
    /**
     * Package Config
     *
     * @var array
     */
    protected $config = [];

    /**
     * Illuminate request class.
     *
     * @var Request
     */
    protected $request;

    /**
     * Default locale
     *
     * @var string
     */
    protected $defaultLocale;

    /**
     * Supported Locales
     *
     * @var array
     */
    protected $supportedLocales;

    /**
     * Current locale
     *
     * @var string
     */
    protected $currentLocale = false;

    /**
     * Creates new locale manager instance.
     *
     * @param array   $config
     * @param Request $request
     * @param string  $defaultLocale
     *
     * @throws UnsupportedLocaleException
     */
    public function __construct(array $config, Request $request, $defaultLocale)
    {
        $this->config = $config;
        $this->request = $request;
        $this->defaultLocale = $defaultLocale;

        // Set default locale
        $supportedLocales = $this->getSupportedLocales();

        // Ensure the default locale is supported
        if (empty($supportedLocales[$this->defaultLocale])) {
            throw new UnsupportedLocaleException('Laravel default locale is not in the supportedLocales array.');
        }
    }

    /**
     * It returns an URL without locale (if it has it)
     * Convenience function wrapping getLocalizedURL(false)
     *
     * @param  string|false $url URL to clean, if false, current url would be taken
     *
     * @return string           URL with no locale in path
     */
    public function getNonLocalizedURL($url = null)
    {
        return $this->getLocalizedURL(false, $url);
    }

    /**
     * Returns default locale
     *
     * @return string
     */
    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

    /**
     * Set and return current locale
     *
     * @param  string $locale Locale to set the App to (optional)
     *
     * @return string                    Returns locale (if route has any) or null (if route does not have a locale)
     */
    public function setLocale($locale = null)
    {
        if (empty($locale) || !is_string($locale)) {
            // If the locale has not been passed through the function
            // it tries to get it from the first segment of the url
            $locale = $this->request->segment(1);
        }

        if (!empty($this->supportedLocales[$locale])) {
            $this->currentLocale = $locale;
        }
        else {
            // if the first segment/locale passed is not valid
            // the system would ask which locale have to take
            // it could be taken by the browser
            // depending on your configuration

            $locale = null;

            $this->currentLocale = $this->getCurrentLocale();
        }

        // Set application locale
        app()->setLocale($this->currentLocale);

        // Regional locale such as de_DE, so formatLocalized works in Carbon
        if ($regional = $this->getCurrentLocaleRegional()) {
            setlocale(LC_TIME, $regional . '.utf8');
        }

        return $locale;
    }

    /**
     * Returns an URL adapted to $locale or current locale
     *
     * @param  string|boolean $locale Locale to adapt, false to remove locale
     * @param  string         $url    URL to adapt. If not passed, the current url would be taken.
     *
     * @throws UnsupportedLocaleException
     *
     * @return string                       URL translated
     */
    public function localizeURL($locale = null, $url = null)
    {
        return $this->getLocalizedURL($locale, $url);
    }

    /**
     * Returns an URL adapted to $locale
     *
     * @throws SupportedLocalesNotDefined
     * @throws UnsupportedLocaleException
     *
     * @param  string|boolean $locale     Locale to adapt, false to remove locale
     * @param  string|false   $url        URL to adapt in the current language. If not passed, the current url would be
     *                                    taken.
     * @param  array          $attributes Attributes to add to the route, if empty, the system would try to extract
     *                                    them from the url.
     *
     *
     * @return string|false                URL translated, False if url does not exist
     */
    public function getLocalizedURL($locale = null, $url = null, $attributes = [])
    {
        // Use default if not set
        if ($locale === null) {
            $locale = $this->getCurrentLocale();
        }

        // Get request Uri
        if (empty($url)) {
            $url = $this->request->getRequestUri();
        }

        // Strip scheme and host
        else {
            $parts = parse_url($url);
            $url = $parts['path'] . (isset($parts['query']) ? '?' . $parts['query'] : '');
        }

        $scheme = $this->request->getScheme();

        $locale = ($locale && $locale !== $this->getDefaultLocale()) ? "{$locale}." : '';

        // Get host
        $array = explode('.', $this->request->getHttpHost());
        $host = (array_key_exists(count($array) - 2, $array) ? $array[count($array) - 2] : '') . '.' . $array[count($array) - 1];

        return app('url')->to("{$scheme}://{$locale}{$host}{$url}", $attributes);
    }

    /**
     * Return an array of all supported Locales
     *
     * @throws SupportedLocalesNotDefined
     * @return array
     */
    public function getSupportedLocales()
    {
        if (empty($this->supportedLocales)) {
            $this->supportedLocales = $this->getConfig('supportedLocales', []);

            if (empty($this->supportedLocales) || !is_array($this->supportedLocales)) {
                throw new SupportedLocalesNotDefined();
            }
        }

        return $this->supportedLocales;
    }

    /**
     * Returns current language
     *
     * @return string current language
     */
    public function getCurrentLocale()
    {
        if ($this->currentLocale) {
            return $this->currentLocale;
        }

        // Get preferred locale from browser
        if ($this->getConfig('useAcceptLanguageHeader', true)) {
            return $this->request->getPreferredLanguage($this->getSupportedLanguagesKeys());
        }

        // or get application default language
        return $this->defaultLocale;
    }

    /**
     * Returns current locale direction
     *
     * @return string current locale direction
     */
    public function getCurrentLocaleDirection()
    {
        if (!empty($this->supportedLocales[$this->getCurrentLocale()]['dir'])) {
            return $this->supportedLocales[$this->getCurrentLocale()]['dir'];
        }

        switch ($this->getCurrentLocaleScript()) {
            // Other (historic) RTL scripts exist, but this list contains the only ones in current use.
            case 'Arab':
            case 'Hebr':
            case 'Mong':
            case 'Tfng':
            case 'Thaa':
                return 'rtl';
            default:
                return 'ltr';
        }

    }

    /**
     * Returns current locale name
     *
     * @return string current locale name
     */
    public function getCurrentLocaleName()
    {
        return $this->supportedLocales[$this->getCurrentLocale()]['name'];
    }

    /**
     * Returns current locale native name
     *
     * @return string current locale native name
     */
    public function getCurrentLocaleNative()
    {
        return $this->supportedLocales[$this->getCurrentLocale()]['native'];
    }

    /**
     * Returns current locale script
     *
     * @return string current locale script
     */
    public function getCurrentLocaleScript()
    {
        return $this->supportedLocales[$this->getCurrentLocale()]['script'];
    }

    /**
     * Returns current language's native reading
     *
     * @return string current language's native reading
     */
    public function getCurrentLocaleNativeReading()
    {
        return $this->supportedLocales[$this->getCurrentLocale()]['native'];
    }

    /**
     * Returns current regional
     *
     * @return string current regional
     */
    public function getCurrentLocaleRegional()
    {
        return $this->supportedLocales[$this->getCurrentLocale()]['regional'];
    }

    /**
     * Returns supported languages language key
     *
     * @return array    keys of supported languages
     */
    public function getSupportedLanguagesKeys()
    {
        return array_keys($this->getSupportedLocales());
    }


    /**
     * Check if Locale exists on the supported locales array
     *
     * @param string|boolean $locale string|bool Locale to be checked
     *
     * @throws SupportedLocalesNotDefined
     * @return boolean is the locale supported?
     */
    public function checkLocaleInSupportedLocales($locale)
    {
        $locales = $this->getSupportedLocales();

        if ($locale !== false && empty($locales[$locale])) {
            return false;
        }

        return true;
    }

    /**
     * Get configuration value.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getConfig($key, $default = null)
    {
        return Arr::get($this->config, $key, $default);
    }
}