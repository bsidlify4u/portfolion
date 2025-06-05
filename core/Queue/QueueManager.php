<?php
namespace Portfolion\Queue;

use Portfolion\Events\Event;
use Portfolion\Queue\Connectors\ConnectorInterface;
use Portfolion\Queue\Connectors\DatabaseConnector;
use Portfolion\Queue\Connectors\RedisConnector;
use Portfolion\Queue\Connectors\SyncConnector;

class QueueManager {
    /**
     * The application instance.
     *
     * @var \Portfolion\Foundation\Application
     */
    protected $app;

    /**
     * The array of resolved queue connections.
     *
     * @var array
     */
    protected array $connections = [];

    /**
     * The array of resolved queue connectors.
     *
     * @var array
     */
    protected array $connectors = [];

    /**
     * Create a new queue manager instance.
     *
     * @param \Portfolion\Foundation\Application $app
     * @return void
     */
    public function __construct($app) {
        $this->app = $app;
        $this->registerConnectors();
    }

    /**
     * Register the default queue connectors.
     *
     * @return void
     */
    protected function registerConnectors(): void {
        $this->registerSyncConnector();
        $this->registerDatabaseConnector();
        $this->registerRedisConnector();
    }

    /**
     * Register the sync queue connector.
     *
     * @return void
     */
    protected function registerSyncConnector(): void {
        $this->registerConnector('sync', function () {
            return new SyncConnector();
        });
    }

    /**
     * Register the database queue connector.
     *
     * @return void
     */
    protected function registerDatabaseConnector(): void {
        $this->registerConnector('database', function () {
            return new DatabaseConnector($this->app['db']);
        });
    }

    /**
     * Register the Redis queue connector.
     *
     * @return void
     */
    protected function registerRedisConnector(): void {
        $this->registerConnector('redis', function () {
            return new RedisConnector($this->app['redis']);
        });
    }

    /**
     * Register a connector.
     *
     * @param string $driver
     * @param \Closure $callback
     * @return void
     */
    public function registerConnector(string $driver, \Closure $callback): void {
        $this->connectors[$driver] = $callback;
    }

    /**
     * Resolve a queue connection instance.
     *
     * @param string|null $name
     * @return \Portfolion\Queue\QueueInterface
     */
    public function connection(?string $name = null): QueueInterface {
        $name = $name ?: $this->getDefaultDriver();

        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->resolve($name);
        }

        return $this->connections[$name];
    }

    /**
     * Resolve a queue connection.
     *
     * @param string $name
     * @return \Portfolion\Queue\QueueInterface
     */
    protected function resolve(string $name): QueueInterface {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new \InvalidArgumentException("Queue connection [{$name}] is not defined.");
        }

        return $this->getConnector($config['driver'])
                    ->connect($config)
                    ->setConnectionName($name);
    }

    /**
     * Get the connector for a given driver.
     *
     * @param string $driver
     * @return ConnectorInterface
     *
     * @throws \InvalidArgumentException
     */
    protected function getConnector(string $driver): ConnectorInterface {
        if (!isset($this->connectors[$driver])) {
            throw new \InvalidArgumentException("No connector for [{$driver}]");
        }

        return call_user_func($this->connectors[$driver]);
    }

    /**
     * Get the queue connection configuration.
     *
     * @param string $name
     * @return array
     */
    protected function getConfig(string $name): array {
        return $this->app['config']["queue.connections.{$name}"];
    }

    /**
     * Get the name of the default queue connection.
     *
     * @return string
     */
    public function getDefaultDriver(): string {
        return $this->app['config']['queue.default'];
    }

    /**
     * Set the name of the default queue connection.
     *
     * @param string $name
     * @return void
     */
    public function setDefaultDriver(string $name): void {
        $this->app['config']['queue.default'] = $name;
    }

    /**
     * Dynamically pass calls to the default connection.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters): mixed {
        return $this->connection()->$method(...$parameters);
    }
}
