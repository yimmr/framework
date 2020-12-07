<?php
namespace Impack\Container;

use Closure;
use Impack\Container\ContainerException;
use InvalidArgumentException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;

class BoundMethod
{
    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param  \Impack\Container\Container  $container
     * @param  callable|string|mixed  $callback
     * @param  array  $params
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public static function call($container, $callback, array $params = [])
    {
        if (\is_string($callback)) {
            static::parseStrCallback($callback);
        }

        if (is_object($callback) && !$callback instanceof Closure) {
            $callback = [$callback, '__invoke'];
        }

        $params = static::getMethodDepend($container, static::getCallReflector($callback), $params);

        return call_user_func_array($callback, $params);
    }

    /**
     * Get all dependencies for a given reflector.
     *
     * @param  \Impack\Container\Container  $container
     * @param  \ReflectionFunctionAbstract  $reflector
     * @param  array  $params
     * @return array
     */
    public static function getMethodDepend($container, $reflector, array $params = [])
    {
        $deps = [];

        foreach ($reflector->getParameters() as $param) {
            static::addDependParameter($container, $param, $params, $deps);
        }

        return $deps;
    }

    /**
     * Parse available callbacks from the string
     *
     * @param string  $callback
     * @return string|array
     *
     * @throws \InvalidArgumentException
     */
    protected static function parseStrCallback(string &$callback)
    {
        if (strpos($callback, '::') !== false) {
            $callback = explode('::', $callback);
        } elseif (strpos($callback, '@') !== false) {
            $callback = explode('@', $callback);
        } elseif (\method_exists($callback, '__invoke')) {
            $callback = [$callback, '__invoke'];
        } else {
            return $callback;
        }

        if (empty($callback[1])) {
            $callback[1] = method_exists($callback[0], '__invoke') ? '__invoke' : null;
        }

        if (is_null($callback[1])) {
            throw new InvalidArgumentException('Method not provided.');
        }
    }

    /**
     * Get the proper reflection instance for the given callback.
     *
     * @param  callable|string  $callback
     * @return \ReflectionFunctionAbstract
     */
    protected static function getCallReflector($callback)
    {
        return is_array($callback)
        ? new ReflectionMethod($callback[0], $callback[1])
        : new ReflectionFunction($callback);
    }

    /**
     * Get the dependency for the given call parameter.
     *
     * @param  \Impack\Container\Container  $container
     * @param  \ReflectionParameter  $param
     * @param  array  $params
     * @param  array  $deps
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected static function addDependParameter($container, $param, array &$params, &$deps)
    {
        if (isset($params[$param->name])) {
            $deps[] = $params[$param->name];

            unset($params[$param->name]);
        } elseif ($param->getClass()) {
            if (isset($params[$param->getClass()->name])) {
                $deps[] = $params[$param->getClass()->name];

                unset($params[$param->getClass()->name]);
            } else {
                $deps[] = static::resolveClass($container, $param);
            }
        } elseif ($params) {
            $deps[] = \array_shift($params);
        } elseif ($param->isDefaultValueAvailable()) {
            $deps[] = $param->getDefaultValue();
        } elseif (!$param->isOptional()) {
            throw new InvalidArgumentException("Unable to resolve dependency [{$param}]");
        }
    }

    /**
     * Build dependent classes
     *
     * @param \Impack\Container\Container  $container
     * @param \ReflectionParameter $param
     * @return mixed
     *
     * @throws \Impack\Container\ContainerException
     */
    protected static function resolveClass($container, ReflectionParameter $param)
    {
        try {
            return $container->make($param->getClass()->name);
        } catch (ContainerException $e) {
            if ($param->isOptional()) {
                return $param->getDefaultValue();
            }
            throw $e;
        }
    }
}