<?php

namespace PmConnect\LaravelParamConverter\Middleware;

use Closure;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;

class RouteSetup
{
    /**
     * @var Router
     */
    protected $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /** @var RouteCollection $routes */
        $routes = $this->router->getRoutes();

        foreach ($routes->getRoutes() as $route) {
            /** @var Route $route */
            $route->middleware([ParamConverter::class]);
        }

        return $next($request);
    }
}
