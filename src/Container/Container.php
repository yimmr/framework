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

    protected $globalResolvingCallbacks = [];

    protected $resolvingCallbacks = [];

    /**
     * Resolve the given type from the container.
     *
     * @param  string  $id
     * @param  array  $params
     * @return mixed
     *
     * @throws \Impack\Container\ContainerException
     */
    public function make($id, array $params = [])
    {
        return $this->resolve($id, $params);
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
     * @param  string  $id
     * @return bool
     */
    public function resolved($id)
    {
        $id = $this->getAlias($id);
        return isset($this->resolved[$id]) || isset($this->instances[$id]);
    }

    /**
     * Determine if a given type is shared.
     *
     * @param  string  $id
     * @return bool
     */
    public function isShared($id)
    {
        return isset($this->instances[$id]) || ($this->bindings[$id]['shared'] ?? false);
    }

    /**
     * Register a binding with the container.
     *
     * @param  string  $id
     * @param  string|null  $concrete
     * @param  bool  $shared
     * @return void
     */
    public function bind($id, $concrete = null, $shared = false)
    {
        $this->dropStaleInstances($id);

        $this->bindings[$id] = [
            'concrete' => $concrete ?: $id,
            'shared'   => $shared,
        ];
    }

    /**
     * Register a binding if it hasn't already been registered.
     *
     * @param  string  $id
     * @param  string|null  $concrete
     * @param  bool  $shared
     * @return void
     */
    public function bindIf($id, $concrete = null, $shared = false)
    {
        if (!$this->has($id)) {
            $this->bind($id, $concrete, $shared);
        }
    }

    /**
     * Register a shared binding in the container.
     *
     * @param  string  $id
     * @param  string|null  $concrete
     * @return void
     */
    public function singleton($id, $concrete = null)
    {
        $this->bind($id, $concrete, true);
    }

    /**
     * Register an existing instance as shared in the container.
     *
     * @param  string  $id
     * @param  mixed  $instance
     * @return mixed
     */
    public function instance($id, $instance)
    {
        $this->removeAlias($id);

        $this->instances[$id] = $instance;

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

        $this->fireResolvingCallbacks($abstract, $object);

        $this->resolved[$abstract] = true;

        return $object;
    }

    /**
     * Get the concrete type for a given abstract.
     *
     * @param  string  $abstract
     * @return mixed
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
     * @return void
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
     * Register a new resolving callback.
     *
     * @param  \Closure|string  $id
     * @param  \Closure|null  $callback
     * @return void
     */
    public function resolving($id, Closure $callback = null)
    {
        if (is_null($callback) && $id instanceof Closure) {
            $this->globalResolvingCallbacks[] = $id;
        } else {
            $this->resolvingCallbacks[$this->getAlias($id)][] = $callback;
        }
    }

    /**
     * Fire all of the resolving callbacks.
     *
     * @param  string  $abstract
     * @param  mixed  $object
     * @return void
     */
    protected function fireResolvingCallbacks($abstract, $object)
    {
        foreach ($this->globalResolvingCallbacks as $callback) {
            $callback($object, $this);
        }

        foreach ($this->resolvingCallbacks as $type => $callback) {
            if ($type === $abstract || $object instanceof $type) {
                $callback($object, $this);
            }
        }
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
     * @param  string  $id
     * @return \Closure
     */
    public function factory($id)
    {
        return function () use ($id) {
            return $this->make($id);
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
     * @return void
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
     * @param  string  $name
     * @return string
     */
    public function getAlias($name)
    {
        return !isset($this->aliases[$name]) ? $name : $this->getAlias($this->aliases[$name]);
    }

    /**
     * Remove an alias from the contextual binding alias cache.
     *
     * @param  string  $searched
     * @return void
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
     * @param  string  $id
     * @return void
     */
    protected function dropStaleInstances($id)
    {
        unset($this->instances[$id], $this->aliases[$id]);
    }

    /**
     * Remove a resolved instance from the instance cache.
     *
     * @param  string  $id
     * @return void
     */
    public function forgetInstance($id = null)
    {
        if (\is_null($id)) {
            $this->instances = [];
        } else {
            unset($this->instances[$id]);
        }
    }

    /**
     * Flush the container of all bindings and resolved instances.
     *
     * @return void
     */
    public function flush()
    {
        $this->aliases   = [];
        $this->resolved  = [];
        $this->bindings  = [];
        $this->instances = [];
    }

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
     * @return void
     */
    public function offsetSet($key, $value)
    {
        if (\is_array($value)) {
            $this->bind($key, ...$value);
        } else {
            $this->bind($key, $value);
        }
    }

    /**
     * Unset the value at a given offset.
     *
     * @param  string  $key
     * @return void
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
     * @return void
     */
    public function __set($key, $value)
    {
        $this[$key] = $value;
    }
}