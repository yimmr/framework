<?php
namespace Impack\Contracts\Container;

use Closure;

interface Container
{
    /**
     * Resolve the given type from the container.
     *
     * @param  string  $id
     * @param  array  $parameters
     * @return mixed
     */
    public function make($id, array $params = []);

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
     * @param  string  $id
     * @return bool
     */
    public function resolved($id);

    /**
     * Determine if a given type is shared.
     *
     * @param  string  $id
     * @return bool
     */
    public function isShared($id);

    /**
     * Register a binding with the container.
     *
     * @param  string  $id
     * @param  \Closure|string|null  $concrete
     * @param  bool  $shared
     * @return void
     */
    public function bind($id, $concrete = null, $shared = false);

    /**
     * Register a binding if it hasn't already been registered.
     *
     * @param  string  $id
     * @param  \Closure|string|null  $concrete
     * @param  bool  $shared
     * @return void
     */
    public function bindIf($id, $concrete = null, $shared = false);

    /**
     * Register a shared binding in the container.
     *
     * @param  string  $id
     * @param  \Closure|string|null  $concrete
     * @return void
     */
    public function singleton($id, $concrete = null);

    /**
     * Register an existing instance as shared in the container.
     *
     * @param  string  $id
     * @param  mixed  $instance
     * @return mixed
     */
    public function instance($id, $instance);

    /**
     * Instantiate a concrete instance of the given type.
     *
     * @param  string  $concrete
     * @param array $params
     * @return mixed
     */
    public function build($concrete, $params = []);

    /**
     * Register a new resolving callback.
     *
     * @param  \Closure|string  $id
     * @param  \Closure|null  $callback
     * @return void
     */
    public function resolving($id, Closure $callback = null);

    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param  callable|string  $callback
     * @param  array|string|mixed  $parameters
     * @return mixed
     */
    public function call($callback, array $parameters = []);

    /**
     * Alias a type to a different name.
     *
     * @param  string  $abstract
     * @param  string  $alias
     * @return void
     */
    public function alias($abstract, $alias);

    /**
     * Flush the container of all bindings and resolved instances.
     *
     * @return void
     */
    public function flush();
}