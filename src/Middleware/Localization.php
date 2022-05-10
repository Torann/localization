<?php

namespace Torann\Localization\Middleware;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Torann\Localization\LocaleManager;

class Localization
{
    protected LocaleManager|null $locale_manager = null;

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     * @throws \Torann\Localization\Exceptions\SupportedLocalesNotDefined
     * @throws \Torann\Localization\Exceptions\UnsupportedLocaleException
     */
    public function handle($request, Closure $next)
    {
        // Don't redirect the console
        if ($this->runningInConsole()) {
            return $next($request);
        }

        // Get current locale
        $current_locale = $this->getLocaleManager()->getCurrentLocale();

        // Determine locale from host or subdomain
        $locale = $this->determineLocale($request);

        // Check first time visitors locale
        if ($locale === config('app.locale') && $this->getUserLocale($request) === null) {
            $this->setUserLocale($current_locale, $request);

            // Redirect to correct locale
            if ($current_locale !== config('app.locale')) {
                $redirection = $this->getLocaleManager()->getLocalizedURL(
                    $request->getRequestUri(), $current_locale
                );

                return new RedirectResponse(
                    $redirection, 301, ['Vary' => 'Accept-Language']
                );
            }
        }

        $this->setLocale($locale);

        return $next($request);
    }

    /**
     * Determine if the application is running in the console.
     *
     * @return bool
     */
    protected function runningInConsole(): bool
    {
        return app()->runningInConsole();
    }

    /**
     * Determine the locale from the current host.
     *
     * @param Request $request
     *
     * @return string
     */
    protected function determineLocale(Request $request): string
    {
        $host = $request->getHost();

        // Get subdomain from host
        $subdomain = explode('.', $host)[0];

        // Validate subdomain
        if ($this->getLocaleManager()->isSupported($subdomain)) {
            return $subdomain;
        }

        // Match hosts
        $default = $this->getLocaleManager()->getDefaultLocale();
        $hosts = $this->getLocaleManager()->getConfig('hosts', []);

        return empty($hosts) ? $default : Arr::get($hosts, $host, $default);
    }

    /**
     * Set the application locale.
     *
     * @param string $locale
     *
     * @return void
     */
    protected function setLocale(string $locale)
    {
        $this->getLocaleManager()->setLocale($locale);
    }

    /**
     * Get the user locale.
     *
     * @param Request $request
     *
     * @return string|null
     */
    protected function getUserLocale(Request $request): string|null
    {
        if ($request->hasSession()) {
            if ($locale = $request->session()->get('locale')) {
                return $locale;
            }
        }

        return null;
    }

    /**
     * Set the user locale.
     *
     * @param string  $locale
     * @param Request $request
     *
     * @return void
     */
    protected function setUserLocale(string $locale, Request $request)
    {
        if ($request->hasSession()) {
            $request->session()->put(['locale' => $locale]);
            $request->session()->keep('locale');
        }
    }

    /**
     * @return LocaleManager
     */
    protected function getLocaleManager(): LocaleManager
    {
        if ($this->locale_manager === null) {
            $this->locale_manager = app(LocaleManager::class);
        }

        return $this->locale_manager;
    }
}
