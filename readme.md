# Laravel Param Converter

Gets the Symfony `@ParamConverter` annotation working within Laravel 5.3+.

## Installation

Install using composer.

```
composer require pm-connect/laravel-param-converter
```

### Setup

Setup is extremely simple, just add the service provider to your `app.php` config.

```
\PmConnect\LaravelParamConverter\ServiceProvider::class,
```

You can also publish the config so you can add your own param converters.

```
php artisan vendor:publish --provider="\PmConnect\LaravelParamConverter\ServiceProvider"
```

### Custom Converters

See the Symfony docs for how to create custom converters [here](http://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/converters.html#creating-a-converter).

Once created, just add the class to the `param-converter.php` config file (as long as you have published your config!) and away you go.