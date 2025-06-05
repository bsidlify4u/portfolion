<?php

namespace Portfolion\Auth;

use Portfolion\Auth\Guards\GuardInterface;
use Portfolion\Auth\Guards\SessionGuard;
use Portfolion\Auth\Guards\TokenGuard;
use Portfolion\Auth\Providers\ProviderInterface;
use Portfolion\Auth\Providers\UserProvider;
use Portfolion\Config;
use InvalidArgumentException;

/**
 * Authentication manager
 */
class Auth
{
    /**
     * The application instance.
     *
     * @var \Portfolion\Application
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
     * @var array<string, GuardInterface>
     */
    protected array $guards = [];

    /**
     * The user providers.
     *
     * @var array<string, ProviderInterface>
     */
    protected array $providers = [];

    /**
     * Create a new Auth manager instance.
     *
     * @param \Portfolion\Application|null $app
     */
    public function __construct($app = null)
    {
        $this->app = $app ?? app();
    }

    /**
     * Attempt to get the guard from the local cache.
     *
     * @param string|null $name
     * @return GuardInterface
     */
    public function guard(?string $name = null): GuardInterface
    {
        $name = $name ?: $this->getDefaultGuard();

        return $this->guards[$name] = $this->guards[$name] ?? $this->resolve($name);
    }

    /**
     * Get the default authentication guard name.
     *
     * @return string
     */
    public function getDefaultGuard(): string
    {
        return Config::getInstance()->get('auth.defaults.guard', 'web');
    }

    /**
     * Resolve the given guard.
     *
     * @param string $name
     * @return GuardInterface
     *
     * @throws InvalidArgumentException
     */
    protected function resolve(string $name): GuardInterface
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Auth guard [{$name}] is not defined.");
        }

        if (isset($this->customCreators[$config['driver']])) {
            return $this->callCustomCreator($name, $config);
        }

        $driverMethod = 'create' . ucfirst($config['driver']) . 'Driver';

        if (method_exists($this, $driverMethod)) {
            return $this->$driverMethod($name, $config);
        }

        throw new InvalidArgumentException("Auth driver [{$config['driver']}] is not supported.");
    }

    /**
     * Call a custom driver creator.
     *
     * @param string $name
     * @param array $config
     * @return GuardInterface
     */
    protected function callCustomCreator(string $name, array $config): GuardInterface
    {
        return $this->customCreators[$config['driver']]($name, $config);
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

        return new SessionGuard($name, $provider, $this->app->get('session'));
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

        return new TokenGuard($name, $provider, $config['storage_key'] ?? 'api_token');
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
            $provider = Config::getInstance()->get('auth.defaults.provider', 'users');
        }

        return Config::getInstance()->get("auth.providers.{$provider}", []);
    }

    /**
     * Create the user provider.
     *
     * @param string|null $provider
     * @return ProviderInterface
     */
    protected function createUserProvider(?string $provider = null): ProviderInterface
    {
        $config = $this->getProviderConfig($provider);

        if (isset($this->customProviderCreators[$config['driver']])) {
            return $this->callCustomProviderCreator($config);
        }

        $driver = $config['driver'] ?? 'database';

        return match ($driver) {
            'database' => new UserProvider($config),
            default => throw new InvalidArgumentException("Authentication provider [{$driver}] is not supported."),
        };
    }

    /**
     * Call a custom provider creator.
     *
     * @param array $config
     * @return ProviderInterface
     */
    protected function callCustomProviderCreator(array $config): ProviderInterface
    {
        return $this->customProviderCreators[$config['driver']]($config);
    }

    /**
     * Get the guard configuration.
     *
     * @param string $name
     * @return array|null
     */
    protected function getConfig(string $name): ?array
    {
        return Config::getInstance()->get("auth.guards.{$name}");
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
        return $this->guard()->$method(...$parameters);
    }
} 