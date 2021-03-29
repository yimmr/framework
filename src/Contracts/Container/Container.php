<?php
namespace Impack\Contracts\Container;

use Closure;

interface Container
{
    /**
     * Resolve the given type from the container.
     *
     * @param  string  $abstract
     * @param  array  $parameters
     * @return mixed
     */
    public function make($abstract, array $params = []);

    /**
     * Determine if the given id type has been bound.
     *
     * @param  string  $id
     * @return bool
     */
    public function has($id);

    /**
     * Determine if the given id type has been resolved.
     *
     * @param  string  $abstract
     * @return bool
     */
    public function resolved($abstract);

    /**
     * Determine if a given type is shared.
     *
     * @param  string  $abstract
     * @return bool
     */
    public function isShared($abstract);

    /**
     * Register a binding with the container.
     *
     * @param  string  $abstract
     * @param  \Closure|string|null  $concrete
     * @param  bool  $shared
     */
    public function bind($abstract, $concrete = null, $shared = false);

    /**
     * Register a binding if it hasn't already been registered.
     *
     * @param  string  $abstract
     * @param  \Closure|string|null  $concrete
     * @param  bool  $shared
     */
    public function bindIf($abstract, $concrete = null, $shared = false);

    /**
     * Register a shared binding in the container.
     *
     * @param  string  $abstract
     * @param  \Closure|string|null  $concrete
     */
    public function singleton($abstract, $concrete = null);

    /**
     * Register an existing instance as shared in the container.
     *
     * @param  string  $abstract
     * @param  mixed  $instance
     * @return mixed
     */
    public function instance($abstract, $instance);

    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param  callable|string  $callback
     * @param  array|string|mixed  $params
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function call($callback, array $parameters = []);

    /**
     * Get a closure to resolve the given type from the container.
     *
     * @param  string  $abstract
     * @return \Closure
     */
    public function factory($abstract);

    /**
     * Alias a type to a different name.
     *
     * @param  string  $abstract
     * @param  string  $alias
     */
    public function alias($abstract, $alias);

    /**
     * Flush the container of all bindings and resolved instances.
     */
    public function flush();
}