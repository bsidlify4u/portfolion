<?php

namespace Portfolion\Auth;

class Gate
{
    /**
     * The application instance.
     *
     * @var \Portfolion\Foundation\Application
     */
    protected $app;

    /**
     * The user resolver callable.
     *
     * @var callable
     */
    protected $userResolver;

    /**
     * All of the defined abilities.
     *
     * @var array
     */
    protected array $abilities = [];

    /**
     * All of the defined policies.
     *
     * @var array
     */
    protected array $policies = [];

    /**
     * All of the registered before callbacks.
     *
     * @var array
     */
    protected array $beforeCallbacks = [];

    /**
     * All of the registered after callbacks.
     *
     * @var array
     */
    protected array $afterCallbacks = [];

    /**
     * Create a new gate instance.
     *
     * @param \Portfolion\Foundation\Application $app
     * @param callable $userResolver
     * @return void
     */
    public function __construct($app, callable $userResolver)
    {
        $this->app = $app;
        $this->userResolver = $userResolver;
    }

    /**
     * Determine if a given ability has been defined.
     *
     * @param string $ability
     * @return bool
     */
    public function has(string $ability): bool
    {
        return isset($this->abilities[$ability]);
    }

    /**
     * Define a new ability.
     *
     * @param string $ability
     * @param callable|string $callback
     * @return $this
     */
    public function define(string $ability, callable|string $callback): self
    {
        if (is_callable($callback)) {
            $this->abilities[$ability] = $callback;
        } elseif (is_string($callback)) {
            $this->abilities[$ability] = $this->buildAbilityCallback($ability, $callback);
        }

        return $this;
    }

    /**
     * Define abilities for a resource.
     *
     * @param string $name
     * @param string $class
     * @param array|null $abilities
     * @return $this
     */
    public function resource(string $name, string $class, ?array $abilities = null): self
    {
        $abilities = $abilities ?: [
            'view' => 'view',
            'create' => 'create',
            'update' => 'update',
            'delete' => 'delete',
        ];

        foreach ($abilities as $ability => $method) {
            $this->define($name.'.'.$ability, $class.'@'.$method);
        }

        return $this;
    }

    /**
     * Create the ability callback for a callback string.
     *
     * @param string $ability
     * @param string $callback
     * @return \Closure
     */
    protected function buildAbilityCallback(string $ability, string $callback): \Closure
    {
        return function () use ($ability, $callback) {
            if (str_contains($callback, '@')) {
                [$class, $method] = explode('@', $callback);
            } else {
                $class = $callback;
                $method = $ability;
            }

            $policy = $this->resolvePolicy($class);

            return $policy->{$method}(...func_get_args());
        };
    }

    /**
     * Define a policy class for a given class type.
     *
     * @param string $class
     * @param string $policy
     * @return $this
     */
    public function policy(string $class, string $policy): self
    {
        $this->policies[$class] = $policy;

        return $this;
    }

    /**
     * Register a callback to run before all Gate checks.
     *
     * @param callable $callback
     * @return $this
     */
    public function before(callable $callback): self
    {
        $this->beforeCallbacks[] = $callback;

        return $this;
    }

    /**
     * Register a callback to run after all Gate checks.
     *
     * @param callable $callback
     * @return $this
     */
    public function after(callable $callback): self
    {
        $this->afterCallbacks[] = $callback;

        return $this;
    }

    /**
     * Determine if the given ability should be granted for the current user.
     *
     * @param string $ability
     * @param array $arguments
     * @return bool
     */
    public function allows(string $ability, array $arguments = []): bool
    {
        return $this->check($ability, $arguments);
    }

    /**
     * Determine if the given ability should be denied for the current user.
     *
     * @param string $ability
     * @param array $arguments
     * @return bool
     */
    public function denies(string $ability, array $arguments = []): bool
    {
        return !$this->allows($ability, $arguments);
    }

    /**
     * Determine if the given ability should be granted for the current user.
     *
     * @param string $ability
     * @param array $arguments
     * @return bool
     */
    public function check(string $ability, array $arguments = []): bool
    {
        try {
            $result = $this->raw($ability, $arguments);
        } catch (\Exception $e) {
            return false;
        }

        return (bool) $result;
    }

    /**
     * Determine if the given ability should be granted for the current user.
     *
     * @param string $ability
     * @param array $arguments
     * @return mixed
     *
     * @throws \Exception
     */
    public function raw(string $ability, array $arguments = []): mixed
    {
        $user = $this->resolveUser();

        // First we will call the "before" callbacks for the Gate. If any of these give
        // back a non-null response, we will immediately return that result in order
        // to let the developers override all checks for some authorization cases.
        $result = $this->callBeforeCallbacks(
            $user, $ability, $arguments
        );

        if (is_null($result)) {
            $result = $this->callAuthCallback($user, $ability, $arguments);
        }

        // After calling the authorization callback, we will call the "after" callbacks
        // that are registered with the Gate, which allows a developer to do logging
        // if that is required for this application. Then we'll return the result.
        $this->callAfterCallbacks(
            $user, $ability, $arguments, $result
        );

        return $result;
    }

    /**
     * Resolve the callable for the given ability.
     *
     * @param string $ability
     * @param array $arguments
     * @return callable
     */
    protected function resolveAuthCallback(string $ability, array $arguments): callable
    {
        if (isset($this->abilities[$ability])) {
            return $this->abilities[$ability];
        }

        if (isset($arguments[0])) {
            return $this->resolvePolicyCallback($ability, $arguments);
        }

        return function () {
            return false;
        };
    }

    /**
     * Resolve the callback for a policy check.
     *
     * @param string $ability
     * @param array $arguments
     * @return callable
     */
    protected function resolvePolicyCallback(string $ability, array $arguments): callable
    {
        $instance = $arguments[0];
        $class = get_class($instance);

        if (isset($this->policies[$class])) {
            $policy = $this->resolvePolicy($this->policies[$class]);

            if (method_exists($policy, $ability)) {
                return function ($user) use ($policy, $ability, $arguments) {
                    return $policy->{$ability}($user, ...$arguments);
                };
            }
        }

        return function () {
            return false;
        };
    }

    /**
     * Resolve the given policy.
     *
     * @param string $class
     * @return mixed
     */
    protected function resolvePolicy(string $class): mixed
    {
        return new $class;
    }

    /**
     * Get a gate instance for the given user.
     *
     * @param mixed $user
     * @return static
     */
    public function forUser(mixed $user): static
    {
        $callback = function () use ($user) {
            return $user;
        };

        return new static($this->app, $callback);
    }

    /**
     * Get the user resolver callback.
     *
     * @return callable
     */
    public function getUserResolver(): callable
    {
        return $this->userResolver;
    }

    /**
     * Set the user resolver callback.
     *
     * @param callable $userResolver
     * @return $this
     */
    public function setUserResolver(callable $userResolver): self
    {
        $this->userResolver = $userResolver;

        return $this;
    }

    /**
     * Get the current user.
     *
     * @return mixed
     */
    protected function resolveUser(): mixed
    {
        return call_user_func($this->userResolver);
    }

    /**
     * Call all of the before callbacks and return if a result is given.
     *
     * @param mixed $user
     * @param string $ability
     * @param array $arguments
     * @return mixed
     */
    protected function callBeforeCallbacks(mixed $user, string $ability, array $arguments): mixed
    {
        $arguments = array_merge([$user, $ability], $arguments);

        foreach ($this->beforeCallbacks as $callback) {
            $result = call_user_func_array($callback, $arguments);

            if (!is_null($result)) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Call the appropriate authorization callback.
     *
     * @param mixed $user
     * @param string $ability
     * @param array $arguments
     * @return mixed
     */
    protected function callAuthCallback(mixed $user, string $ability, array $arguments): mixed
    {
        $callback = $this->resolveAuthCallback($ability, $arguments);

        return call_user_func_array($callback, array_merge([$user], $arguments));
    }

    /**
     * Call all of the after callbacks with check result.
     *
     * @param mixed $user
     * @param string $ability
     * @param array $arguments
     * @param mixed $result
     * @return void
     */
    protected function callAfterCallbacks(mixed $user, string $ability, array $arguments, mixed $result): void
    {
        $arguments = array_merge([$user, $ability, $result], $arguments);

        foreach ($this->afterCallbacks as $callback) {
            call_user_func_array($callback, $arguments);
        }
    }
} 