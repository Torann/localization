<?php

namespace Torann\Localization;

use Illuminate\Support\ServiceProvider;

class LocalizationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/localization.php', 'localization'
        );
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(LocaleManager::class, function ($app) {
            return new LocaleManager(
                $app->config->get('localization', []),
                $app['request'],
                $app['url'],
                $app->config->get('app.locale')
            );
        });

        if ($this->app->runningInConsole() && $this->isLumen() === false) {
            $this->publishes([
                __DIR__ . '/../config/localization.php' => config_path('localization.php'),
            ], 'config');
        }
    }

    /**
     * Check if package is running under Lumen app
     *
     * @return bool
     */
    protected function isLumen(): bool
    {
        return str_contains($this->app->version(), 'Lumen') === true;
    }
}
