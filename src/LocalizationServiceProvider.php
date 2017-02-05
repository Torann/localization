<?php

namespace Torann\Localization;

use Illuminate\Support\ServiceProvider;

class LocalizationServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->isLumen() === false) {
            $this->publishes([
                __DIR__ . '/../../config/localization.php' => config_path('localization.php'),
            ]);

            $this->mergeConfigFrom(
                __DIR__ . '/../../config/localization.php', 'localization'
            );
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(LocaleManager::class, function ($app) {
            $config = $app->config->get('localization', []);

            return new LocaleManager($config, $app['request'], $app->config->get('app.locale'));
        });
    }

    /**
     * Check if package is running under Lumen app
     *
     * @return bool
     */
    protected function isLumen()
    {
        return str_contains($this->app->version(), 'Lumen') === true;
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}