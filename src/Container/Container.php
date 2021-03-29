<?php
namespace Impack\Container;

use ArrayAccess;
use Closure;
use Impack\Container\BoundMethod;
use Impack\Container\ContainerException;
use Impack\Contracts\Container\Container as ContainerContract;
use LogicException;
use ReflectionClass;
use ReflectionException;

class Container implements ArrayAccess, ContainerContract
{
    protected static $instance;

    protected $resolved = [];

    protected $bindings = [];

    protected $instances = [];

    protected $aliases = [];

    protected $buildStack = [];

    /**
     * Resolve the given type from the container.
     *
     * @param  string  $abstract
     * @param  array  $params
     * @return mixed
     *
     * @throws \Impack\Container\ContainerException
     */
    public function make($abstract, array $params = [])
    {
        return $this->resolve($abstract, $params);
    }

    /**
     * Determine if the given id type has been bound.
     *
     * @param  string  $id
     * @return bool
     */
    public function has($id)
    {
        return isset($this->bindings[$id]) || isset($this->instances[$id]) || $this->isAlias($id);
    }

    /**
     * Determine if the given id type has been resolved.
     *
     * @param  string  $abstract
     * @return bool
     */
    public function resolved($abstract)
    {
        $abstract = $this->getAlias($abstract);
        return isset($this->resolved[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * Determine if a given type is shared.
     *
     * @param  string  $abstract
     * @return bool
     */
    public function isShared($abstract)
    {
        return isset($this->instances[$abstract]) || ($this->bindings[$abstract]['shared'] ?? false);
    }

    /**
     * Register a binding with the container.
     *
     * @param  string  $abstract
     * @param  \Closure|string|null  $concrete
     * @param  bool  $shared
     */
    public function bind($abstract, $concrete = null, $shared = false)
    {
        $this->dropStaleInstances($abstract);

        $this->bindings[$abstract] = [
            'concrete' => $concrete ?: $abstract,
            'shared'   => $shared,
        ];
    }

    /**
     * Register a binding if it hasn't already been registered.
     *
     * @param  string  $abstract
     * @param  \Closure|string|null  $concrete
     * @param  bool  $shared
     */
    public function bindIf($abstract, $concrete = null, $shared = false)
    {
        if (!$this->has($abstract)) {
            $this->bind($abstract, $concrete, $shared);
        }
    }

    /**
     * Register a shared binding in the container.
     *
     * @param  string  $abstract
     * @param  \Closure|string|null  $concrete
     */
    public function singleton($abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Register an existing instance as shared in the container.
     *
     * @param  string  $abstract
     * @param  mixed  $instance
     * @return mixed
     */
    public function instance($abstract, $instance)
    {
        $this->removeAlias($abstract);

        $this->instances[$abstract] = $instance;

        return $instance;
    }

    /**
     * Resolve the given type from the container.
     *
     * @param  string  $abstract
     * @param  array  $params
     * @return mixed
     *
     * @throws \Impack\Container\ContainerException
     */
    protected function resolve($abstract, $params = [])
    {
        $abstract = $this->getAlias($abstract);

        $noContextual = empty($params);

        if (isset($this->instances[$abstract]) && $noContextual) {
            return $this->instances[$abstract];
        }

        $object = $this->build($this->getConcrete($abstract), $params);

        if ($this->isShared($abstract) && $noContextual) {
            $this->instances[$abstract] = $object;
        }

        $this->resolved[$abstract] = true;

        return $object;
    }

    /**
     * Get the concrete type for a given abstract.
     *
     * @param  string  $abstract
     * @return \Closure|array|string|null
     */
    protected function getConcrete($abstract)
    {
        return isset($this->bindings[$abstract]) ? $this->bindings[$abstract]['concrete'] : $abstract;
    }

    /**
     * Instantiate a concrete instance of the given type.
     *
     * @param string  $concrete
     * @param array $params
     * @return mixed
     *
     * @throws \Impack\Container\ContainerException
     */
    public function build($concrete, $params = [])
    {
        if ($concrete instanceof Closure) {
            return $concrete(...$params);
        }

        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new ContainerException("Target class [$concrete] does not exist.", 0, $e);
        }

        if (!$reflector->isInstantiable()) {
            return $this->notInstantiable($concrete);
        }

        $this->buildStack[] = $concrete;

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            array_pop($this->buildStack);
            return new $concrete;
        }

        $params = BoundMethod::getMethodDepend($this, $constructor, $params);

        array_pop($this->buildStack);

        return $reflector->newInstanceArgs($params);
    }

    /**
     * Throw an exception that the concrete is not instantiable.
     *
     * @param  string  $concrete
     *
     * @throws \Impack\Container\ContainerException
     */
    protected function notInstantiable($concrete)
    {
        if (!empty($this->buildStack)) {
            $previous = implode(', ', $this->buildStack);
            $message  = "Target [$concrete] is not instantiable while building [$previous].";
        } else {
            $message = "Target [$concrete] is not instantiable.";
        }

        throw new ContainerException($message);
    }

    /**
     * Get the container's bindings.
     *
     * @return array
     */
    public function getBindings()
    {
        return $this->bindings;
    }

    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param  callable|string  $callback
     * @param  array|string|mixed  $params
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function call($callback, array $params = [])
    {
        return BoundMethod::call($this, $callback, $params);
    }

    /**
     * Get a closure to resolve the given type from the container.
     *
     * @param  string  $abstract
     * @return \Closure
     */
    public function factory($abstract)
    {
        return function () use ($abstract) {
            return $this->make($abstract);
        };
    }

    /**
     * Wrap the given closure such that its dependencies will be injected when executed.
     *
     * @param  \Closure  $callback
     * @param  array  $params
     * @return \Closure
     */
    public function wrap(Closure $callback, array $params = [])
    {
        return function () use ($callback, $params) {
            return $this->call($callback, $params);
        };
    }

    /**
     * Alias a type to a different name.
     *
     * @param  string  $abstract
     * @param  string  $alias
     *
     * @throws \LogicException
     */
    public function alias($abstract, $alias)
    {
        if ($alias === $abstract) {
            throw new LogicException("[{$abstract}] is aliased to itself.");
        }

        $this->aliases[$alias] = $abstract;
    }

    /**
     * Determine if a given string is an alias.
     *
     * @param  string  $name
     * @return bool
     */
    public function isAlias($name)
    {
        return isset($this->aliases[$name]);
    }

    /**
     * Get the alias for an abstract if available.
     *
     * @param  string  $abstract
     * @return string
     */
    public function getAlias($abstract)
    {
        return !isset($this->aliases[$abstract]) ? $abstract : $this->getAlias($this->aliases[$abstract]);
    }

    /**
     * Remove an alias from the contextual binding alias cache.
     *
     * @param  string  $searched
     */
    protected function removeAlias($searched)
    {
        if (isset($this->aliases[$searched])) {
            unset($this->aliases[$searched]);
        }
    }

    /**
     * Drop all of the stale instances and aliases.
     *
     * @param  string  $abstract
     */
    protected function dropStaleInstances($abstract)
    {
        unset($this->instances[$abstract], $this->aliases[$abstract]);
    }

    /**
     * Remove a resolved instance from the instance cache.
     *
     * @param  string  $abstract
     */
    public function forgetInstance($abstract)
    {
        unset($this->instances[$abstract]);
    }

    /**
     * Flush the container of all bindings and resolved instances.
     */
    public function flush()
    {
        $this->aliases   = [];
        $this->resolved  = [];
        $this->bindings  = [];
        $this->instances = [];
    }

    /**
     * Resolve the given type from the container.
     *
     * @param string $id
     * @param array $params
     * @return mixed
     */
    public function get($id, array $params = [])
    {
        return $this->make($id, $params);
    }

    /**
     * Get the globally available instance of the container.
     *
     * @return static
     */
    public static function getInstance()
    {
        return static::$instance ?? static::$instance = new static;
    }

    /**
     * Set the shared instance of the container.
     *
     * @param  \Impack\Contracts\Container\Container|null  $container
     * @return \Impack\Contracts\Container\Container|static
     */
    public static function setInstance(ContainerContract $container = null)
    {
        return static::$instance = $container;
    }

    /**
     * Determine if a given offset exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * Get the value at a given offset.
     *
     * @param  string  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->make($key);
    }

    /**
     * Set the value at a given offset.
     *
     * @param  string  $key
     * @param  mixed  $value
     */
    public function offsetSet($key, $value)
    {
        if (is_array($value)) {
            $this->bind($key, ...$value);
        } else {
            $this->bind($key, $value);
        }
    }

    /**
     * Unset the value at a given offset.
     *
     * @param  string  $key
     */
    public function offsetUnset($key)
    {
        unset($this->bindings[$key], $this->instances[$key], $this->resolved[$key]);
    }

    /**
     * Dynamically access container services.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this[$key];
    }

    /**
     * Dynamically set container services.
     *
     * @param  string  $key
     * @param  mixed  $value
     */
    public function __set($key, $value)
    {
        $this[$key] = $value;
    }
}