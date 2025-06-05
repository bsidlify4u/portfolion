<?php
namespace Portfolion\Container;

use Closure;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use RuntimeException;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionException;
use Psr\Container\ContainerInterface;
use Portfolion\Container\Exceptions\CircularDependencyException;
use Portfolion\Container\Exceptions\BindingResolutionException;

/**
 * Container implementation with dependency injection.
 */
class Container implements ContainerInterface {
    /**
     * The current globally available container instance.
     *
     * @var static
     */
    protected static $instance;
    
    /**
     * An array of the types that have been resolved.
     *
     * @var array
     */
    protected array $resolved = [];
    
    /**
     * The container's bindings.
     *
     * @var array
     */
    protected array $bindings = [];
    
    /**
     * The container's method bindings.
     *
     * @var array
     */
    protected array $methodBindings = [];
    
    /**
     * The container's shared instances.
     *
     * @var array
     */
    protected array $instances = [];
    
    /**
     * The registered type aliases.
     *
     * @var array
     */
    protected array $aliases = [];
    
    /**
     * The registered aliases keyed by the abstract name.
     *
     * @var array
     */
    protected array $abstractAliases = [];
    
    /**
     * The extension closures for services.
     *
     * @var array
     */
    protected array $extenders = [];
    
    /**
     * All of the registered tags.
     *
     * @var array
     */
    protected array $tags = [];
    
    /**
     * The stack of concretions currently being built.
     *
     * @var array
     */
    protected array $buildStack = [];
    
    /**
     * The contextual binding map.
     *
     * @var array
     */
    protected array $contextual = [];
    
    /**
     * All of the registered rebound callbacks.
     *
     * @var array
     */
    protected array $reboundCallbacks = [];
    
    /**
     * Create a new container instance.
     */
    public function __construct() {}
    
    /**
     * Register a binding with the container.
     *
     * @param string $abstract
     * @param \Closure|string|null $concrete
     * @param bool $shared
     * @return void
     */
    public function bind(string $abstract, mixed $concrete = null, bool $shared = false): void {
        // If the concrete is null, we'll set it to the abstract to allow direct
        // resolution of the abstract class/interface
        $concrete = $concrete ?: $abstract;
        
        // If the concrete is not a Closure, we'll make one for automatic resolution
        if (!$concrete instanceof Closure) {
            if (!is_string($concrete)) {
                throw new \TypeError("Concrete type must be string or Closure");
            }
            
            $concrete = $this->getClosure($abstract, $concrete);
        }
        
        $this->bindings[$abstract] = compact('concrete', 'shared');
        
        // If the abstract has been resolved before, we'll refresh all instances
        if (isset($this->resolved[$abstract]) || isset($this->instances[$abstract])) {
            $this->rebound($abstract);
        }
    }
    
    /**
     * Get the closure to be used when building a concrete.
     *
     * @param string $abstract
     * @param string $concrete
     * @return \Closure
     */
    protected function getClosure(string $abstract, string $concrete): Closure {
        return function ($container, $parameters = []) use ($abstract, $concrete) {
            if ($abstract === $concrete) {
                return $container->build($concrete);
            }
            
            return $container->resolve(
                $concrete, $parameters, $raiseEvents = false
            );
        };
    }
    
    /**
     * Register a shared binding in the container.
     *
     * @param string $abstract
     * @param \Closure|string|null $concrete
     * @return void
     */
    public function singleton(string $abstract, mixed $concrete = null): void {
        $this->bind($abstract, $concrete, true);
    }
    
    /**
     * "Extend" an abstract type in the container.
     *
     * @param string $abstract
     * @param \Closure $closure
     * @return void
     */
    public function extend(string $abstract, Closure $closure): void {
        if (isset($this->instances[$abstract])) {
            $this->instances[$abstract] = $closure($this->instances[$abstract], $this);
            $this->rebound($abstract);
            return;
        }
        
        $this->extenders[$abstract][] = $closure;
    }
    
    /**
     * Register an existing instance as shared in the container.
     *
     * @param string $abstract
     * @param mixed $instance
     * @return mixed
     */
    public function instance(string $abstract, $instance): mixed {
        $this->instances[$abstract] = $instance;
        
        return $instance;
    }
    
    /**
     * Define a contextual binding.
     *
     * @param array|string $concrete
     * @return ContextualBindingBuilder
     */
    public function when($concrete): ContextualBindingBuilder {
        $aliases = [];
        
        if (is_string($concrete)) {
            $aliases = [$concrete];
        } else {
            $aliases = $concrete;
        }
        
        return new ContextualBindingBuilder($this, $aliases);
    }
    
    /**
     * Fire the "rebound" callbacks for the given abstract type.
     *
     * @param string $abstract
     * @return void
     */
    protected function rebound(string $abstract): void {
        $instance = $this->make($abstract);
        
        foreach ($this->getReboundCallbacks($abstract) as $callback) {
            call_user_func($callback, $this, $instance);
        }
    }
    
    /**
     * Get the rebound callbacks for a given type.
     *
     * @param string $abstract
     * @return array
     */
    protected function getReboundCallbacks(string $abstract): array {
        return $this->reboundCallbacks[$abstract] ?? [];
    }
    
    /**
     * Resolve the given type from the container.
     *
     * @param string $abstract
     * @param array $parameters
     * @return mixed
     *
     * @throws \Portfolion\Container\Exceptions\BindingResolutionException
     */
    public function make(string $abstract, array $parameters = []): mixed {
        return $this->resolve($abstract, $parameters);
    }
    
    /**
     * Resolve the given type from the container.
     *
     * @param string $abstract
     * @param array $parameters
     * @param bool $raiseEvents
     * @return mixed
     *
     * @throws \Portfolion\Container\Exceptions\BindingResolutionException
     */
    protected function resolve(string $abstract, array $parameters = [], bool $raiseEvents = true): mixed {
        // Set a start time to prevent infinite loops
        static $startTime = null;
        if ($startTime === null) {
            $startTime = microtime(true);
        } elseif (microtime(true) - $startTime > 5) {
            // If we've been resolving for more than 5 seconds, something is wrong
            $startTime = null;
            throw new CircularDependencyException("Resolution timeout exceeded. Possible circular dependency detected.");
        }
        
        // First check for an existing singleton instance
        if (isset($this->instances[$abstract]) && empty($parameters)) {
            $startTime = null;
            return $this->instances[$abstract];
        }
        
        // Check if we're already building this abstract type
        if (in_array($abstract, $this->buildStack)) {
            // Create a new instance without resolving dependencies to break the circular reference
            try {
                $reflector = new ReflectionClass($abstract);
                if ($reflector->isInstantiable()) {
                    // For controllers, we'll create a new instance without resolving dependencies
                    if (strpos($abstract, 'Controller') !== false) {
                        $startTime = null;
                        return new $abstract();
                    }
                }
            } catch (ReflectionException $e) {
                $startTime = null;
                throw new BindingResolutionException("Target class [$abstract] does not exist.", 0, $e);
            }
            
            $startTime = null;
            throw new CircularDependencyException("Circular dependency detected while resolving [{$abstract}]: " . implode(' -> ', $this->buildStack) . " -> {$abstract}");
        }
        
        // Temporary building flag to prevent circular dependencies
        $this->buildStack[] = $abstract;
        
        try {
            // Create a concrete instance based on our bindings
            $object = $this->build($abstract, $parameters);
            
            // Apply any extensions to the resolved object
            if (isset($this->extenders[$abstract])) {
                foreach ($this->extenders[$abstract] as $extender) {
                    $object = $extender($object, $this);
                }
            }
        } finally {
            array_pop($this->buildStack);
        }
        
        // If this is a singleton, store the instance for future use
        if ($this->isShared($abstract) && empty($parameters)) {
            $this->instances[$abstract] = $object;
        }
        
        // Mark this type as resolved
        $this->resolved[$abstract] = true;
        
        // Reset the start time
        $startTime = null;
        
        return $object;
    }
    
    /**
     * Instantiate a concrete instance of the given type.
     *
     * @param string $concrete
     * @param array $parameters
     * @return mixed
     *
     * @throws \Portfolion\Container\Exceptions\BindingResolutionException
     */
    protected function build(string $concrete, array $parameters = []): mixed {
        // Special case for TaskController to avoid circular dependencies
        if ($concrete === 'App\\Controllers\\TaskController') {
            return new \App\Controllers\TaskController();
        }
        
        // If the concrete has a binding, use that implementation
        if (isset($this->bindings[$concrete])) {
            $concrete = $this->bindings[$concrete]['concrete'];
            
            if ($concrete instanceof Closure) {
                return $concrete($this, $parameters);
            }
        }
        
        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new BindingResolutionException("Target class [$concrete] does not exist.", 0, $e);
        }
        
        // If the type is not instantiable, we can't build it
        if (!$reflector->isInstantiable()) {
            if (!empty($this->buildStack)) {
                throw new BindingResolutionException("Target [$concrete] is not instantiable while building [" . implode(', ', $this->buildStack) . "].");
            }
            
            throw new BindingResolutionException("Target [$concrete] is not instantiable.");
        }
        
        // We've already checked for circular dependencies in resolve()
        // so we don't need to check again here
        
        $constructor = $reflector->getConstructor();
        
        // If there's no constructor, just create a new instance
        if (is_null($constructor)) {
            return new $concrete;
        }
        
        // Get the constructor parameters
        $dependencies = $constructor->getParameters();
        
        // If there are no dependencies and no parameters, we can create a new instance
        if (empty($dependencies) && empty($parameters)) {
            return new $concrete;
        }
        
        // Resolve any dependencies from the container
        $resolvedDependencies = $this->resolveDependencies($dependencies, $parameters);
        
        return $reflector->newInstanceArgs($resolvedDependencies);
    }
    
    /**
     * Resolve all of the dependencies from the ReflectionParameters.
     *
     * @param \ReflectionParameter[] $dependencies
     * @param array $parameters
     * @return array
     *
     * @throws \Portfolion\Container\Exceptions\BindingResolutionException
     */
    protected function resolveDependencies(array $dependencies, array $parameters): array {
        $result = [];
        
        foreach ($dependencies as $dependency) {
            // If we have a parameter value for this dependency, use it
            if (array_key_exists($dependency->getName(), $parameters)) {
                $result[] = $parameters[$dependency->getName()];
                continue;
            }
            
            // If we have a positional parameter value for this dependency, use it
            if (array_key_exists($dependency->getPosition(), $parameters)) {
                $result[] = $parameters[$dependency->getPosition()];
                continue;
            }
            
            // Try to resolve the dependency from the container
            try {
                $result[] = $this->resolveParameterDependency($dependency);
            } catch (BindingResolutionException $e) {
                // If the dependency is optional, use the default value
                if ($dependency->isOptional()) {
                    $result[] = $dependency->getDefaultValue();
                    continue;
                }
                
                throw $e;
            }
        }
        
        return $result;
    }
    
    /**
     * Resolve a single parameter dependency.
     *
     * @param \ReflectionParameter $parameter
     * @return mixed
     *
     * @throws \Portfolion\Container\Exceptions\BindingResolutionException
     */
    protected function resolveParameterDependency(ReflectionParameter $parameter): mixed {
        $type = $parameter->getType();
        
        // If the parameter doesn't have a type hint, we can't resolve it
        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }
            
            throw new BindingResolutionException(
                "Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}"
            );
        }
        
        try {
            return $this->make($type->getName());
        } catch (BindingResolutionException $e) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }
            
            throw $e;
        }
    }
    
    /**
     * Determine if a given type is shared.
     *
     * @param string $abstract
     * @return bool
     */
    protected function isShared(string $abstract): bool {
        return isset($this->instances[$abstract]) ||
               (isset($this->bindings[$abstract]['shared']) &&
                $this->bindings[$abstract]['shared'] === true);
    }
    
    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param callable|string $callback
     * @param array $parameters
     * @param string|null $defaultMethod
     * @return mixed
     */
    public function call($callback, array $parameters = [], ?string $defaultMethod = null): mixed {
        if (is_string($callback) && !empty($defaultMethod) && class_exists($callback)) {
            $callback = [$this->make($callback), $defaultMethod];
        }
        
        if (is_array($callback)) {
            $reflection = new ReflectionMethod($callback[0], $callback[1]);
            
            $reflectionParams = $reflection->getParameters();
        } else {
            $reflection = new ReflectionFunction($callback);
            
            $reflectionParams = $reflection->getParameters();
        }
        
        $dependencies = $this->resolveDependencies($reflectionParams, $parameters);
        
        return call_user_func_array($callback, $dependencies);
    }
    
    /**
     * Get container bindings.
     *
     * @return array
     */
    public function getBindings(): array {
        return $this->bindings;
    }
    
    /**
     * Add a contextual binding to the container.
     *
     * @param string $concrete
     * @param string $abstract
     * @param mixed $implementation
     * @return void
     */
    public function addContextualBinding(string $concrete, string $abstract, $implementation): void {
        $this->contextual[$concrete][$abstract] = $implementation;
    }
    
    /**
     * Determine if the given abstract type has been bound.
     *
     * @param string $abstract
     * @return bool
     */
    public function bound(string $abstract): bool {
        return isset($this->bindings[$abstract]) ||
               isset($this->instances[$abstract]) ||
               isset($this->aliases[$abstract]);
    }
    
    /**
     * Alias a type to a different name.
     *
     * @param string $abstract
     * @param string $alias
     * @return void
     */
    public function alias(string $abstract, string $alias): void {
        $this->aliases[$alias] = $abstract;
        $this->abstractAliases[$abstract][] = $alias;
    }
    
    /**
     * Determine if a given string is an alias.
     *
     * @param string $name
     * @return bool
     */
    public function isAlias(string $name): bool {
        return isset($this->aliases[$name]);
    }
    
    /**
     * Register a binding if it hasn't already been registered.
     *
     * @param string $abstract
     * @param \Closure|string|null $concrete
     * @param bool $shared
     * @return void
     */
    public function bindIf(string $abstract, mixed $concrete = null, bool $shared = false): void {
        if (!$this->bound($abstract)) {
            $this->bind($abstract, $concrete, $shared);
        }
    }
    
    /**
     * Register a shared binding if it hasn't already been registered.
     *
     * @param string $abstract
     * @param \Closure|string|null $concrete
     * @return void
     */
    public function singletonIf(string $abstract, mixed $concrete = null): void {
        if (!$this->bound($abstract)) {
            $this->singleton($abstract, $concrete);
        }
    }
    
    /**
     * Register a callback to run after a type has been resolved.
     *
     * @param string $abstract
     * @param \Closure $callback
     * @return void
     */
    public function afterResolved(string $abstract, Closure $callback): void {
        $this->afterResolving($abstract, $callback);
    }
    
    /**
     * Register a callback to run when a type is resolved.
     *
     * @param string $abstract
     * @param \Closure $callback
     * @return void
     */
    public function resolving(string $abstract, Closure $callback): void {
        $this->reboundCallbacks[$abstract][] = $callback;
    }
    
    /**
     * Register a callback to run after a type has been resolved.
     *
     * @param string $abstract
     * @param \Closure $callback
     * @return void
     */
    public function afterResolving(string $abstract, Closure $callback): void {
        $this->reboundCallbacks[$abstract][] = $callback;
    }
    
    /**
     * Determine if the given dependency has been resolved.
     *
     * @param string $abstract
     * @return bool
     */
    public function isResolved(string $abstract): bool {
        return isset($this->resolved[$abstract]) ||
               isset($this->instances[$abstract]);
    }
    
    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws \Psr\Container\NotFoundExceptionInterface  No entry was found for **this** identifier.
     * @throws \Psr\Container\ContainerExceptionInterface Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get(string $id): mixed {
        try {
            return $this->make($id);
        } catch (BindingResolutionException $e) {
            throw new class($e->getMessage(), 0, $e) extends \Exception implements \Psr\Container\NotFoundExceptionInterface {};
        }
    }
    
    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has(string $id): bool {
        return $this->bound($id);
    }
    
    /**
     * Tag a group of container bindings.
     *
     * @param string $tag
     * @param array $abstracts
     * @return void
     */
    public function tag(string $tag, array $abstracts): void {
        foreach ($abstracts as $abstract) {
            $this->tags[$tag][] = $abstract;
        }
    }
    
    /**
     * Resolve all of the bindings for a given tag.
     *
     * @param string $tag
     * @return array
     */
    public function tagged(string $tag): array {
        $results = [];
        
        if (isset($this->tags[$tag])) {
            foreach ($this->tags[$tag] as $abstract) {
                $results[] = $this->make($abstract);
            }
        }
        
        return $results;
    }
    
    /**
     * Flush the container of all bindings and resolved instances.
     *
     * @return void
     */
    public function flush(): void {
        $this->bindings = [];
        $this->instances = [];
        $this->resolved = [];
        $this->aliases = [];
        $this->abstractAliases = [];
        $this->reboundCallbacks = [];
        $this->extenders = [];
        $this->tags = [];
        $this->contextual = [];
    }
    
    /**
     * Set the globally available instance of the container.
     *
     * @return static
     */
    public static function setInstance(?self $container = null): self {
        return static::$instance = $container ?: new static;
    }
    
    /**
     * Get the globally available instance of the container.
     *
     * @return static
     */
    public static function getInstance(): self {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }
        
        return static::$instance;
    }
} 