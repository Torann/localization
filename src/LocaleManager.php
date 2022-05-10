<?php

namespace Torann\Localization;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Routing\UrlGenerator;
use Torann\Localization\Exceptions\UnsupportedLocaleException;
use Torann\Localization\Exceptions\SupportedLocalesNotDefined;

class LocaleManager
{
    protected array $config = [];
    protected Request $request;
    protected UrlGenerator $url;

    protected string $default_locale;
    protected string|null $current_locale = null;

    /**
     * @param array        $config
     * @param Request      $request
     * @param UrlGenerator $url
     * @param string       $default_locale
     *
     * @throws UnsupportedLocaleException
     * @throws SupportedLocalesNotDefined
     */
    public function __construct(array $config, Request $request, UrlGenerator $url, string $default_locale)
    {
        $this->config = $config;
        $this->request = $request;
        $this->url = $url;
        $this->default_locale = $default_locale;

        $supported_locales = $this->getLocales();

        if (empty($supported_locales) || is_array($supported_locales) === false) {
            throw new SupportedLocalesNotDefined();
        }

        if (isset($supported_locales[$this->default_locale]) === false) {
            throw new UnsupportedLocaleException(
                'Laravel default locale is not in the supported_locales array.'
            );
        }
    }

    /**
     * Returns default locale
     *
     * @return string
     */
    public function getDefaultLocale(): string
    {
        return $this->default_locale;
    }

    /**
     * Returns current language
     *
     * @return string
     */
    public function getCurrentLocale(): string
    {
        if ($this->current_locale) {
            return $this->current_locale;
        }

        // Get preferred locale from browser
        if ($this->getConfig('use_accept_language_header', true)) {
            return $this->request->getPreferredLanguage(
                $this->getLocaleKeys()
            );
        }

        // or get application default language
        return $this->getDefaultLocale();
    }

    /**
     * Set and return current locale
     *
     * @param string|null $locale
     *
     * @return string|null
     */
    public function setLocale(string $locale = null): string|null
    {
        if (empty($locale) || is_string($locale) === false) {
            // If the locale has not been passed through the function
            // it tries to get it from the first segment of the url
            $locale = $this->request->segment(1);
        }

        if (array_key_exists($locale, $this->getLocales())) {
            $this->current_locale = $locale;
        } else {
            $this->current_locale = $this->getCurrentLocale();
        }

        // Set application locale
        app()->setLocale($this->current_locale);

        // Regional locale such as de_DE, so formatLocalized works in Carbon
        if ($regional = $this->getLocale('current.regional')) {
            setlocale(LC_TIME, "{$regional}.utf8");
        }

        return $this->current_locale;
    }

    /**
     * Return an array of all supported Locales
     *
     * @return array
     */
    public function getLocales(): array
    {
        return $this->getConfig('supported_locales', []);
    }

    /**
     * Return the specified locale array or array value
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getLocale(string $key, mixed $default = null): mixed
    {
        // Convert the placeholder "current" into the current locale code
        $key = preg_replace('/^current/i', $this->getCurrentLocale(), $key);

        return Arr::get($this->getLocales(), $key, $default);
    }

    /**
     * Returns specified locale direction
     *
     * @param string $locale
     *
     * @return string
     */
    public function getLocaleDirection(string $locale = 'current'): string
    {
        $dir = $this->getLocale("{$locale}.dir");

        if (empty($dir)) {
            $dir = match ($this->getLocale("{$locale}.script")) {
                'Arab', 'Hebr', 'Mong', 'Tfng', 'Thaa' => 'rtl',
                default => 'ltr',
            };
        }

        return $dir;
    }

    /**
     * Returns supported languages language key
     *
     * @return array
     */
    public function getLocaleKeys(): array
    {
        return array_keys($this->getLocales());
    }

    /**
     * Returns a URL adapted to provided locale or current locale
     *
     * @param string|null $url
     * @param mixed       $locale
     * @param array       $extra
     *
     * @return string
     */
    public function getLocalizedURL(string $url = null, mixed $locale = false, array $extra = []): string
    {
        // Use default if not set
        if ($locale === null) {
            $locale = $this->getCurrentLocale();
        }

        if (empty($url)) {
            $url = $this->request->getRequestUri();
        } else {
            // Strip scheme and host
            $parts = parse_url($url);
            $url = Arr::get($parts, 'path') . (isset($parts['query']) ? '?' . $parts['query'] : '');
        }

        // Get the locale
        $locale = ($locale && $locale !== $this->getDefaultLocale()) ? "{$locale}." : '';

        // Get url parts
        $schema = $this->getSchema();
        $array = explode('.', $this->request->getHttpHost());
        $host = (array_key_exists(count($array) - 2, $array) ? $array[count($array) - 2] : '')
            . '.' . $array[count($array) - 1];

        return $this->url->to("{$schema}://{$locale}{$host}{$url}", $extra);
    }

    /**
     * Check if specified locale is supported
     *
     * @param mixed $key
     *
     * @return bool
     */
    public function isSupported(mixed $key): bool
    {
        return $key && array_key_exists($key, $this->getLocales());
    }

    /**
     * Get configuration value.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->config, $key, $default);
    }

    /**
     * Get the system preferred schema.
     *
     * @return string
     */
    protected function getSchema(): string
    {
        $schema = $this->url->formatScheme();

        return preg_replace('/[^A-Za-z]/', '', $schema);
    }
}
