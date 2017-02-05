<?php

namespace Torann\Localization\Middleware;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Torann\Localization\LocaleManager;
use Illuminate\Foundation\Application;

class Localization
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * The locale manager instance.
     *
     * @var \Torann\Localization\LocaleManager
     */
    protected $localeManager;

    /**
     * Create a new middleware instance.
     *
     * @param Application   $app
     * @param LocaleManager $localeManager
     */
    public function __construct(Application $app, LocaleManager $localeManager)
    {
        $this->app = $app;
        $this->localeManager = $localeManager;
    }

    /**
     * Handle an incoming request.
     *
     * @param  Request $request
     * @param  Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Don't redirect the console or when posting
        if ($this->isReading($request) === false || $this->runningInConsole()) {
            return $next($request);
        }

        // Get current locale
        $currentLocale = $this->localeManager->getCurrentLocale();

        // Determine locale from host or subdomain
        $locale = $this->determineLocale($request);

        // Check first time visitors locale
        if ($locale === config('app.locale') && $this->getUserLocale($request) === null) {
            $this->setUserLocale($currentLocale, $request);

            // Redirect to correct locale
            if ($currentLocale !== config('app.locale')) {
                $redirection = $this->localeManager->getLocalizedURL($currentLocale);

                return new RedirectResponse($redirection, 301, ['Vary' => 'Accept-Language']);
            }
        }

        $this->setLocale($locale);

        return $next($request);
    }

    /**
     * Determine if the HTTP request uses a ‘read’ verb.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return bool
     */
    protected function isReading($request)
    {
        return in_array($request->method(), ['HEAD', 'GET', 'OPTIONS']);
    }

    /**
     * Determine if the application is running in the console.
     *
     * @return bool
     */
    protected function runningInConsole()
    {
        return $this->app->runningInConsole();
    }

    /**
     * Determine the locale from the current host.
     *
     * @param Request $request
     *
     * @return string
     */
    protected function determineLocale($request)
    {
        $host = $request->getHost();

        // Get subdomain from host
        $subdomain = explode('.', $host)[0];

        // Validate subdomain
        if ($this->localeManager->checkLocaleInSupportedLocales($subdomain)) {
            return $subdomain;
        }

        // Match hosts
        $default = $this->localeManager->getDefaultLocale();
        $hosts = $this->localeManager->getConfig('hosts', []);

        return empty($hosts) ? $default : Arr::get($hosts, $host, $default);
    }

    /**
     * Set the application locale.
     *
     * @param string $locale
     */
    protected function setLocale($locale)
    {
        $this->localeManager->setLocale($locale);
    }

    /**
     * Get the user locale.
     *
     * @param Request $request
     *
     * @return string
     */
    protected function getUserLocale($request)
    {
        return $request->getSession()->get('locale', null);
    }

    /**
     * Set the user locale.
     *
     * @param string  $locale
     * @param Request $request
     */
    protected function setUserLocale($locale, $request)
    {
        $request->getSession()->put(['locale' => $locale]);
        $request->getSession()->reflash();
    }
}