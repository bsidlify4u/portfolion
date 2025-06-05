<?php

namespace Portfolion\Auth;

use Portfolion\Auth\Guards\GuardInterface;
use Portfolion\Auth\Guards\SessionGuard;
use Portfolion\Auth\Guards\TokenGuard;
use Portfolion\Auth\Providers\ProviderInterface;
use Portfolion\Auth\Providers\UserProvider;
use Portfolion\Session\SessionManager;

class AuthManager
{
    /**
     * The application instance.
     *
     * @var \Portfolion\Foundation\Application
     */
    protected $app;

    /**
     * The registered custom driver creators.
     *
     * @var array
     */
    protected array $customCreators = [];

    /**
     * The registered custom provider creators.
     *
     * @var array
     */
    protected array $customProviderCreators = [];

    /**
     * The guard instances.
     *
     * @var array
     */
    protected array $guards = [];

    /**
     * The user resolver callback.
     *
     * @var \Closure|null
     */
    protected $userResolver;

    /**
     * Create a new Auth manager instance.
     *
     * @param \Portfolion\Foundation\Application $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Attempt to get the guard from the local cache.
     *
     * @param string|null $name
     * @return GuardInterface
     */
    public function guard(?string $name = null): GuardInterface
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->guards[$name] ?? ($this->guards[$name] = $this->resolve($name));
    }

    /**
     * Resolve the given guard.
     *
     * @param string $name
     * @return GuardInterface
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve(string $name): GuardInterface
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new \InvalidArgumentException("Auth guard [{$name}] is not defined.");
        }

        if (isset($this->customCreators[$config['driver']])) {
            return $this->callCustomCreator($name, $config);
        }

        $driverMethod = 'create' . ucfirst($config['driver']) . 'Driver';

        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}($name, $config);
        }

        throw new \InvalidArgumentException("Auth driver [{$config['driver']}] for guard [{$name}] is not defined.");
    }

    /**
     * Call a custom driver creator.
     *
     * @param string $name
     * @param array $config
     * @return mixed
     */
    protected function callCustomCreator(string $name, array $config): mixed
    {
        return $this->customCreators[$config['driver']]($this->app, $name, $config);
    }

    /**
     * Create a session based authentication guard.
     *
     * @param string $name
     * @param array $config
     * @return GuardInterface
     */
    protected function createSessionDriver(string $name, array $config): GuardInterface
    {
        $provider = $this->createUserProvider($config['provider'] ?? null);

        $guard = new SessionGuard(
            $name,
            $provider,
            $this->app->make(SessionManager::class)
        );

        return $guard;
    }

    /**
     * Create a token based authentication guard.
     *
     * @param string $name
     * @param array $config
     * @return GuardInterface
     */
    protected function createTokenDriver(string $name, array $config): GuardInterface
    {
        $provider = $this->createUserProvider($config['provider'] ?? null);

        $guard = new TokenGuard(
            $name,
            $provider,
            $this->app->make('request'),
            $config
        );

        return $guard;
    }

    /**
     * Get the user provider configuration.
     *
     * @param string|null $provider
     * @return array
     */
    protected function getProviderConfig(?string $provider): array
    {
        if ($provider === null) {
            $provider = $this->getDefaultUserProvider();
        }

        $providers = $this->app['config']['auth.providers'];

        if (isset($providers[$provider])) {
            return $providers[$provider];
        }

        throw new \InvalidArgumentException("Auth provider [{$provider}] is not defined.");
    }

    /**
     * Create the user provider implementation for the driver.
     *
     * @param string|null $provider
     * @return ProviderInterface
     *
     * @throws \InvalidArgumentException
     */
    protected function createUserProvider(?string $provider = null): ProviderInterface
    {
        $config = $this->getProviderConfig($provider);

        if (isset($this->customProviderCreators[$config['driver']])) {
            return call_user_func(
                $this->customProviderCreators[$config['driver']],
                $this->app,
                $config
            );
        }

        switch ($config['driver']) {
            case 'database':
                return $this->createDatabaseProvider($config);
            case 'eloquent':
                return $this->createEloquentProvider($config);
            default:
                throw new \InvalidArgumentException(
                    "Authentication user provider [{$config['driver']}] is not defined."
                );
        }
    }

    /**
     * Create an instance of the database user provider.
     *
     * @param array $config
     * @return ProviderInterface
     */
    protected function createDatabaseProvider(array $config): ProviderInterface
    {
        return new UserProvider($config);
    }

    /**
     * Create an instance of the Eloquent user provider.
     *
     * @param array $config
     * @return ProviderInterface
     */
    protected function createEloquentProvider(array $config): ProviderInterface
    {
        return new UserProvider($config);
    }

    /**
     * Get the guard configuration.
     *
     * @param string $name
     * @return array
     */
    protected function getConfig(string $name): array
    {
        return $this->app['config']["auth.guards.{$name}"];
    }

    /**
     * Get the default authentication driver name.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return $this->app['config']['auth.defaults.guard'];
    }

    /**
     * Set the default guard driver the factory should serve.
     *
     * @param string $name
     * @return void
     */
    public function shouldUse(string $name): void
    {
        $this->setDefaultDriver($name);

        $this->userResolver = function ($guard = null) use ($name) {
            return $this->guard($guard ?: $name)->user();
        };
    }

    /**
     * Set the default authentication driver name.
     *
     * @param string $name
     * @return void
     */
    public function setDefaultDriver(string $name): void
    {
        $this->app['config']['auth.defaults.guard'] = $name;
    }

    /**
     * Get the default user provider name.
     *
     * @return string
     */
    public function getDefaultUserProvider(): string
    {
        return $this->app['config']['auth.defaults.provider'];
    }

    /**
     * Register a custom driver creator Closure.
     *
     * @param string $driver
     * @param \Closure $callback
     * @return $this
     */
    public function extend(string $driver, \Closure $callback): self
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * Register a custom provider creator Closure.
     *
     * @param string $name
     * @param \Closure $callback
     * @return $this
     */
    public function provider(string $name, \Closure $callback): self
    {
        $this->customProviderCreators[$name] = $callback;

        return $this;
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->guard()->{$method}(...$parameters);
    }
} 