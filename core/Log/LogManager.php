<?php
namespace Portfolion\Log;

use Monolog\Logger;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\SyslogHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\NullHandler;
use Monolog\Processor\PsrLogMessageProcessor;

class LogManager
{
    /**
     * The application instance.
     */
    protected $app;

    /**
     * The array of resolved loggers.
     */
    protected array $loggers = [];

    /**
     * Create a new Log manager instance.
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Get a log channel instance.
     */
    public function channel(?string $channel = null): LoggerInterface
    {
        return $this->driver($channel);
    }

    /**
     * Get a log driver instance.
     */
    public function driver(?string $driver = null): LoggerInterface
    {
        return $this->get($driver ?? $this->getDefaultDriver());
    }

    /**
     * Get the default log driver name.
     */
    public function getDefaultDriver(): string
    {
        return $this->app['config']->get('logging.default', 'stack');
    }

    /**
     * Get a log channel instance.
     */
    protected function get(string $name): LoggerInterface
    {
        return $this->loggers[$name] ?? $this->resolve($name);
    }

    /**
     * Resolve the given log instance by name.
     */
    protected function resolve(string $name): LoggerInterface
    {
        $config = $this->app['config']->get("logging.channels.{$name}");

        if (is_null($config)) {
            throw new InvalidArgumentException("Log channel [{$name}] is not defined.");
        }

        if (isset($this->customCreators[$config['driver']])) {
            return $this->callCustomCreator($config);
        }

        $driverMethod = 'create'.ucfirst($config['driver']).'Driver';

        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}($config);
        }

        throw new InvalidArgumentException("Driver [{$config['driver']}] is not supported.");
    }

    /**
     * Create a custom log driver instance.
     */
    protected function createCustomDriver(array $config)
    {
        $factory = $this->customCreators[$config['driver']];

        return $factory($config);
    }

    /**
     * Create an emergency log handler to avoid white screens of death.
     */
    protected function createEmergencyLogger(): LoggerInterface
    {
        $logger = new Logger('portfolion');
        $logger->pushHandler(
            new StreamHandler(storage_path('logs/emergency.log'), Logger::EMERGENCY, true)
        );

        return $logger;
    }

    /**
     * Create an aggregate log driver instance.
     */
    protected function createStackDriver(array $config): LoggerInterface
    {
        $handlers = [];

        foreach ($config['channels'] ?? [] as $channel) {
            $handlers = array_merge($handlers, $this->channel($channel)->getHandlers());
        }

        $logger = new Logger($this->parseChannel($config));
        
        foreach ($handlers as $handler) {
            $logger->pushHandler($handler);
        }

        return $logger;
    }

    /**
     * Create an instance of the single file log driver.
     */
    protected function createSingleDriver(array $config): LoggerInterface
    {
        $logger = new Logger($this->parseChannel($config));

        $handler = new StreamHandler(
            $config['path'],
            $this->level($config),
            $config['bubble'] ?? true,
            $config['permission'] ?? null
        );

        $logger->pushHandler($this->prepareHandler(
            $handler, $config
        ));

        return $logger;
    }

    /**
     * Create an instance of the daily file log driver.
     */
    protected function createDailyDriver(array $config): LoggerInterface
    {
        $logger = new Logger($this->parseChannel($config));

        $handler = new RotatingFileHandler(
            $config['path'],
            $config['days'] ?? 7,
            $this->level($config),
            $config['bubble'] ?? true,
            $config['permission'] ?? null
        );

        $logger->pushHandler($this->prepareHandler(
            $handler, $config
        ));

        return $logger;
    }

    /**
     * Create an instance of the syslog log driver.
     */
    protected function createSyslogDriver(array $config): LoggerInterface
    {
        $logger = new Logger($this->parseChannel($config));

        $handler = new SyslogHandler(
            $config['ident'] ?? 'portfolion',
            $config['facility'] ?? LOG_USER,
            $this->level($config)
        );

        $logger->pushHandler($this->prepareHandler(
            $handler, $config
        ));

        return $logger;
    }

    /**
     * Create an instance of the "error log" log driver.
     */
    protected function createErrorlogDriver(array $config): LoggerInterface
    {
        $logger = new Logger($this->parseChannel($config));

        $handler = new ErrorLogHandler(
            $config['type'] ?? ErrorLogHandler::OPERATING_SYSTEM,
            $this->level($config)
        );

        $logger->pushHandler($this->prepareHandler(
            $handler, $config
        ));

        return $logger;
    }

    /**
     * Create an instance of the null log driver.
     */
    protected function createNullDriver(): LoggerInterface
    {
        $logger = new Logger('null');
        $logger->pushHandler(new NullHandler);

        return $logger;
    }

    /**
     * Prepare the handlers for usage by Monolog.
     */
    protected function prepareHandler($handler, array $config)
    {
        if (!isset($config['formatter'])) {
            $handler->setFormatter($this->formatter());
        } elseif ($config['formatter'] !== 'default') {
            $handler->setFormatter($this->app->make($config['formatter'], $config['formatter_with'] ?? []));
        }

        return $handler;
    }

    /**
     * Get a Monolog formatter instance.
     */
    protected function formatter()
    {
        $formatter = new LineFormatter(null, null, true, true);
        $formatter->includeStacktraces();

        return $formatter;
    }

    /**
     * Parse the string level into a Monolog constant.
     */
    protected function level(array $config)
    {
        $level = $config['level'] ?? 'debug';

        return Logger::toMonologLevel($level);
    }

    /**
     * Parse the channel name.
     */
    protected function parseChannel(array $config): string
    {
        return $config['name'] ?? $this->app['config']->get('app.name', 'portfolion');
    }

    /**
     * Get the default log channel.
     */
    public function stack(array $channels, ?string $channel = null): LoggerInterface
    {
        return $this->createStackDriver([
            'channels' => $channels,
            'name' => $channel,
        ]);
    }

    /**
     * Create a new, on-demand aggregate logger instance.
     */
    public function build(array $config): LoggerInterface
    {
        $logger = new Logger($config['name'] ?? $this->app['config']->get('app.name', 'portfolion'));

        $logger->pushProcessor(new PsrLogMessageProcessor);

        foreach ($config['handlers'] ?? [] as $handler) {
            $logger->pushHandler($handler);
        }

        return $logger;
    }

    /**
     * Register a new callback based log driver.
     */
    public function extend(string $driver, callable $callback): self
    {
        $this->customCreators[$driver] = $callback;
        
        return $this;
    }

    /**
     * System is unusable.
     */
    public function emergency($message, array $context = []): void
    {
        $this->driver()->emergency($message, $context);
    }

    /**
     * Action must be taken immediately.
     */
    public function alert($message, array $context = []): void
    {
        $this->driver()->alert($message, $context);
    }

    /**
     * Critical conditions.
     */
    public function critical($message, array $context = []): void
    {
        $this->driver()->critical($message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     */
    public function error($message, array $context = []): void
    {
        $this->driver()->error($message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     */
    public function warning($message, array $context = []): void
    {
        $this->driver()->warning($message, $context);
    }

    /**
     * Normal but significant events.
     */
    public function notice($message, array $context = []): void
    {
        $this->driver()->notice($message, $context);
    }

    /**
     * Interesting events.
     */
    public function info($message, array $context = []): void
    {
        $this->driver()->info($message, $context);
    }

    /**
     * Detailed debug information.
     */
    public function debug($message, array $context = []): void
    {
        $this->driver()->debug($message, $context);
    }

    /**
     * Log a message at a level.
     */
    public function log($level, $message, array $context = []): void
    {
        $this->driver()->log($level, $message, $context);
    }
} 