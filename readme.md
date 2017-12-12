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

#### Annotation Registry Loader

Depending on the setup you have, you may need to setup doctrines `AnnotationRegistry` class and tell it how to autoload classes.

This can be done by adding the following into a `boot` method of a service provider.

```php
AnnotationRegistry::registerLoader('class_exists');
```

### Custom Converters

See the Symfony docs for how to create custom converters [here](http://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/converters.html#creating-a-converter).

Once created, just add the class to the `param-converter.php` config file (as long as you have published your config!) and away you go.
