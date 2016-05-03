# Laravel Localization - Simplified

[![Latest Stable Version](https://poser.pugx.org/torann/localization/v/stable.png)](https://packagist.org/packages/torann/localization) [![Total Downloads](https://poser.pugx.org/torann/localization/downloads.png)](https://packagist.org/packages/torann/localization)

Simplified localization for Laravel based on the application's subdomain.

## Table of Contents

- [Installation](#installation])
  - [Composer](#composer)
  - [Laravel](#laravel)
- [Methods](#methods)
- [Helpers](#helpers)
- [Determining Locale](#determining-locale)
- [License](#license)
- [Localization on Packagist](https://packagist.org/packages/torann/localization)
- [Localization on GitHub](https://github.com/Torann/localization)

## Installation

### Composer

From the command line run:

```
$ composer require torann/localization
```

### Laravel

Once installed you need to register the service provider with the application. Open up `config/app.php` and find the `providers` key.

``` php
'providers' => [

    \Torann\Localization\LocalizationServiceProvider::class,

]
```

### Publish the configurations

Run this on the command line from the root of your project:

```bash
php artisan vendor:publish --provider="Torann\Localization\LocalizationServiceProvider"
```

A configuration file will be publish to `config/localization.php`.


## Methods

The following methods are available:

### Torann\Localization\LocaleManager

- getNonLocalizedURL($url = null)
- getDefaultLocale()
- setLocale($locale = null)
- localizeURL($locale = null, $url = null)
- getLocalizedURL($locale = null, $url = null, $attributes = [])
- getSupportedLocales()
- getCurrentLocale()
- getCurrentLocaleDirection()
- getCurrentLocaleName()
- getCurrentLocaleNative()
- getCurrentLocaleScript()
- getCurrentLocaleNativeReading()
- getSupportedLanguagesKeys()
- checkLocaleInSupportedLocales($locale)
- getConfig($key, $default = null)

## Helpers

Laravel Localization comes with a few helper methods

### `localization()`

Returns the `Torann\Localization\LocaleManager` instance.

### `localize_url($locale = null, $url = null, $attributes = [])`

Returns the given URL adapted to provided locale.

## Determining Locale

### By Subdomain

For this to work the subdomain needs to match an enabled supported locale key.

### By Host

When the given subdomain is determined to not be valid the system can then set the locale depending on the current host. You'll need to set a map of your application's locales to hosts using the **hosts** configuration option.

### License

Localization is open-sourced software licensed under the BSD 2-Clause License.
