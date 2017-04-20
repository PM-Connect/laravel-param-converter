<?php

namespace PmConnect\LaravelParamConverter;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use PmConnect\LaravelParamConverter\Middleware\RouteSetup;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;

class ServiceProvider extends BaseServiceProvider
{
    public function register()
    {
        /** @var Container $app */
        $app = $this->app;

        /** @var Repository $config */
        $config = $app->make(Repository::class);

        /** @var ParamConverterInterface[] $converters */
        $converters = $config->get('param-converter.converters', []);

        foreach ($converters as $converter) {
            $app->singleton($converter, function (Container $app) use ($converter) {
                return $app->make($converter);
            });
        }
    }

    public function boot()
    {
        /** @var Container $app */
        $app = $this->app;

        $this->publishes([$this->configPath() => config_path('param-converter.php')]);

        /** @var \Illuminate\Foundation\Http\Kernel $kernel */
        $kernel = $app->make(Kernel::class);

        if (! $kernel->hasMiddleware(RouteSetup::class)) {
            $kernel->prependMiddleware(RouteSetup::class);
        }
    }

    protected function configPath()
    {
        return __DIR__ . '/../config/param-converter.php';
    }
}
