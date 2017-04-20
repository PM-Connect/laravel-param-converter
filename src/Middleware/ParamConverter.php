<?php

namespace PmConnect\LaravelParamConverter\Middleware;

use Closure;
use Doctrine\Common\Annotations\AnnotationReader;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter as ParamConverterAnnotation;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ParamConverter
{
    /**
     * @var AnnotationReader
     */
    protected $annotationReader;

    /**
     * @var Container
     */
    protected $container;

    public function __construct(AnnotationReader $annotationReader, Container $container)
    {
        $this->annotationReader = $annotationReader;
        $this->container = $container;
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
        $route = $request->route();

        if ($route && is_string($route->getAction()['uses'] ?? null)) {
            $this->handleParamConversion($request, $route);
        }

        return $next($request);
    }

    protected function handleParamConversion(Request $request, Route $route)
    {
        $controller = Str::parseCallback($route->getAction()['uses']);

        if (count($controller) < 2) {
            return;
        }

        $reflection = new \ReflectionMethod($controller[0], $controller[1]);

        $parameters = $reflection->getParameters();

        $annotations = $this->annotationReader->getMethodAnnotations($reflection);

        $this->processAnnotations($request, $route, $annotations, $parameters);
    }

    protected function processAnnotations(Request $request, Route $route, array $annotations, array $parameters)
    {
        foreach ($annotations as $annotation) {
            if (! $annotation instanceof ParamConverterAnnotation) {
                continue;
            }

            $matchingParameter = $this->getParamConverterParameter($annotation, $parameters);

            if (! $matchingParameter) {
                throw new NotFoundHttpException(sprintf('Param "%s" does not exist on the controller method.', $annotation->getName()));
            }

            $this->setRequestAttributes($request, $route, $annotation);
            $this->setAnnotationClassFromReflection($annotation, $matchingParameter);

            $value = $this->processConverters($request, $annotation);

            if (! $value && ! $matchingParameter->getClass()) {
                throw new NotFoundHttpException(sprintf('Converters returned null value for "%s" and cannot be handled by laravel.', $annotation->getName()));
            }

            if ($value) {
                $route->setParameter($annotation->getName(), $value);
            }
        }
    }

    protected function getParamConverterParameter(ParamConverterAnnotation $paramConverter, array $parameters)
    {
        return array_first(array_filter($parameters, function (\ReflectionParameter $param) use ($paramConverter) {
            return $param->getName() == $paramConverter->getName();
        }));
    }

    protected function setRequestAttributes(Request $request, Route $route, ParamConverterAnnotation $paramConverter)
    {
        if (! $request->attributes->has($paramConverter->getName()) && $route->hasParameter($paramConverter->getName())) {
            $request->attributes->set($paramConverter->getName(), $route->parameter($paramConverter->getName()));
        }
    }

    protected function setAnnotationClassFromReflection(ParamConverterAnnotation $paramConverter, \ReflectionParameter $parameter)
    {
        if ($parameter->getClass() && ! $paramConverter->getClass()) {
            $paramConverter->setClass($parameter->getClass()->getName());
        }
    }

    protected function processConverters(Request $request, ParamConverterAnnotation $paramConverter)
    {
        $converters = $this->getConverters($paramConverter);

        if (! count($converters)) {
            throw new NotFoundHttpException(sprintf('No converters available for "%s" and cannot be handled by laravel.', $paramConverter->getName()));
        }

        foreach ($converters as $converter) {
            if ($converter->apply($request, $paramConverter)) {
                return $request->attributes->get($paramConverter->getName());
            }
        }

        if ($paramConverter->isOptional()) {
            return null;
        } else {
            throw new NotFoundHttpException(sprintf('Non optional converters returned null value for "%s".', $paramConverter->getName()));
        }
    }

    protected function getConverters(ParamConverterAnnotation $paramConverter)
    {
        if ($overrideConverter = $paramConverter->getConverter()) {
            return [$this->container->make($overrideConverter)];
        } else {
            /** @var Repository $config */
            $config = $this->container->make(Repository::class);

            /** @var ParamConverterInterface[] $converters */
            $converters = array_map(function ($converter) {
                return $this->container->make($converter);
            }, $config->get('param-converter.converters'));

            return array_filter($converters, function (ParamConverterInterface $converter) use ($paramConverter) {
                return $converter->supports($paramConverter);
            });
        }
    }
}
