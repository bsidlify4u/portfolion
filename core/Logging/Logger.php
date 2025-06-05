<?php

namespace Portfolion\Logging;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Portfolion\Config;
use InvalidArgumentException;
use RuntimeException;

/**
 * Enhanced PSR-3 compatible logger with support for multiple channels and structured logging
 */
class Logger implements LoggerInterface
{
    /**
     * All log levels in ascending order of severity
     * 
     * @var array
     */
    protected const LOG_LEVELS = [
        LogLevel::DEBUG,
        LogLevel::INFO,
        LogLevel::NOTICE,
        LogLevel::WARNING,
        LogLevel::ERROR,
        LogLevel::CRITICAL,
        LogLevel::ALERT,
        LogLevel::EMERGENCY,
    ];
    
    /**
     * @var array Log handlers for each channel
     */
    protected array $handlers = [];
    
    /**
     * @var array Cached channel loggers
     */
    protected array $channels = [];
    
    /**
     * @var string Default channel name
     */
    protected string $defaultChannel;
    
    /**
     * @var Config Configuration instance
     */
    protected Config $config;
    
    /**
     * @var array Log processors
     */
    protected array $processors = [];
    
    /**
     * Create a new logger instance
     */
    public function __construct()
    {
        $this->config = Config::getInstance();
        $this->defaultChannel = $this->config->get('logging.default', 'single');
        
        // Initialize configured channels
        $this->initializeChannels();
        
        // Add global processors
        $this->initializeProcessors();
    }
    
    /**
     * Initialize configured channels from configuration
     */
    protected function initializeChannels(): void
    {
        $channels = $this->config->get('logging.channels', []);
        
        foreach ($channels as $name => $config) {
            $driver = $config['driver'] ?? null;
            
            if (!$driver) {
                continue;
            }
            
            $this->createHandler($name, $driver, $config);
        }
    }
    
    /**
     * Initialize log processors
     */
    protected function initializeProcessors(): void
    {
        $processors = $this->config->get('logging.processors', []);
        
        foreach ($processors as $processor) {
            if (is_callable($processor)) {
                $this->pushProcessor($processor);
                continue;
            }
            
            if (is_string($processor) && class_exists($processor)) {
                $instance = new $processor();
                if (is_callable($instance)) {
                    $this->pushProcessor($instance);
                }
            }
        }
    }
    
    /**
     * Create a handler for a channel
     * 
     * @param string $channel Channel name
     * @param string $driver Driver name
     * @param array $config Handler configuration
     * @throws InvalidArgumentException When driver is invalid
     */
    protected function createHandler(string $channel, string $driver, array $config): void
    {
        switch ($driver) {
            case 'single':
                $handler = new Handlers\FileHandler(
                    $config['path'] ?? storage_path('logs/app.log'),
                    $config['level'] ?? LogLevel::DEBUG,
                    $config['bubble'] ?? true,
                    $config['permission'] ?? null,
                    $config['locking'] ?? false
                );
                break;
                
            case 'daily':
                $handler = new Handlers\RotatingFileHandler(
                    $config['path'] ?? storage_path('logs/app.log'),
                    $config['days'] ?? 7,
                    $config['level'] ?? LogLevel::DEBUG,
                    $config['bubble'] ?? true,
                    $config['permission'] ?? null,
                    $config['locking'] ?? false
                );
                break;
                
            case 'syslog':
                $handler = new Handlers\SyslogHandler(
                    $config['ident'] ?? 'portfolion',
                    $config['facility'] ?? LOG_USER,
                    $config['level'] ?? LogLevel::DEBUG,
                    $config['bubble'] ?? true
                );
                break;
                
            case 'errorlog':
                $handler = new Handlers\ErrorLogHandler(
                    $config['message_type'] ?? Handlers\ErrorLogHandler::OPERATING_SYSTEM,
                    $config['level'] ?? LogLevel::DEBUG,
                    $config['bubble'] ?? true
                );
                break;
                
            case 'null':
                $handler = new Handlers\NullHandler();
                break;
                
            case 'stack':
                $handler = $this->createStackHandler($config);
                break;
                
            default:
                throw new InvalidArgumentException("Driver [{$driver}] is not supported.");
        }
        
        // Set formatter if specified
        if (isset($config['formatter'])) {
            $formatter = $this->createFormatter($config['formatter']);
            $handler->setFormatter($formatter);
        }
        
        // Add channel-specific processors
        if (isset($config['processors']) && is_array($config['processors'])) {
            foreach ($config['processors'] as $processor) {
                if (is_callable($processor)) {
                    $handler->pushProcessor($processor);
                } elseif (is_string($processor) && class_exists($processor)) {
                    $instance = new $processor();
                    if (is_callable($instance)) {
                        $handler->pushProcessor($instance);
                    }
                }
            }
        }
        
        $this->handlers[$channel] = $handler;
    }
    
    /**
     * Create a stack handler that wraps multiple handlers
     * 
     * @param array $config Stack configuration
     * @return Handlers\HandlerStack
     */
    protected function createStackHandler(array $config): Handlers\HandlerStack
    {
        $handlers = [];
        
        foreach ($config['channels'] ?? [] as $channelName) {
            // If the handler for this channel already exists, use it
            if (isset($this->handlers[$channelName])) {
                $handlers[] = $this->handlers[$channelName];
            } else {
                // Otherwise, try to create it from config
                $channelConfig = $this->config->get("logging.channels.{$channelName}");
                if ($channelConfig) {
                    $driver = $channelConfig['driver'] ?? null;
                    if ($driver && $driver !== 'stack') { // Avoid stack recursion
                        $this->createHandler($channelName, $driver, $channelConfig);
                        $handlers[] = $this->handlers[$channelName];
                    }
                }
            }
        }
        
        return new Handlers\HandlerStack($handlers, $config['bubble'] ?? true);
    }
    
    /**
     * Create a formatter based on configuration
     * 
     * @param array|string $config Formatter configuration
     * @return Formatters\FormatterInterface
     */
    protected function createFormatter($config): Formatters\FormatterInterface
    {
        if (is_string($config)) {
            $type = $config;
            $options = [];
        } else {
            $type = $config['type'] ?? 'line';
            $options = $config;
        }
        
        switch ($type) {
            case 'line':
                return new Formatters\LineFormatter(
                    $options['format'] ?? "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                    $options['date_format'] ?? 'Y-m-d H:i:s',
                    $options['allow_inline_line_breaks'] ?? false,
                    $options['ignore_empty_context_and_extra'] ?? false
                );
                
            case 'json':
                return new Formatters\JsonFormatter(
                    $options['batch_mode'] ?? Formatters\JsonFormatter::BATCH_MODE_JSON,
                    $options['append_new_line'] ?? true
                );
                
            case 'html':
                return new Formatters\HtmlFormatter(
                    $options['date_format'] ?? 'Y-m-d H:i:s'
                );
                
            default:
                if (class_exists($type)) {
                    return new $type(...array_values($options));
                }
                
                throw new InvalidArgumentException("Formatter [{$type}] is not supported.");
        }
    }
    
    /**
     * Add a processor to all handlers
     * 
     * @param callable $processor
     * @return $this
     */
    public function pushProcessor(callable $processor): self
    {
        array_unshift($this->processors, $processor);
        
        return $this;
    }
    
    /**
     * Apply all processors to a record
     * 
     * @param array $record
     * @return array
     */
    protected function processRecord(array $record): array
    {
        foreach ($this->processors as $processor) {
            $record = $processor($record);
        }
        
        return $record;
    }
    
    /**
     * Get a logger for a specific channel
     * 
     * @param string|null $channel
     * @return ChannelLogger
     * @throws InvalidArgumentException
     */
    public function channel(?string $channel = null): ChannelLogger
    {
        $channel = $channel ?: $this->defaultChannel;
        
        if (!isset($this->handlers[$channel])) {
            throw new InvalidArgumentException("Log channel [{$channel}] is not configured.");
        }
        
        if (!isset($this->channels[$channel])) {
            $this->channels[$channel] = new ChannelLogger($channel, $this->handlers[$channel], $this->processors);
        }
        
        return $this->channels[$channel];
    }
    
    /**
     * Log a message with an arbitrary level
     * 
     * @param mixed $level
     * @param string $message
     * @param array $context
     */
    public function log($level, $message, array $context = []): void
    {
        $this->channel()->log($level, $message, $context);
    }
    
    /**
     * System is unusable
     * 
     * @param string $message
     * @param array $context
     */
    public function emergency($message, array $context = []): void
    {
        $this->channel()->emergency($message, $context);
    }
    
    /**
     * Action must be taken immediately
     * 
     * @param string $message
     * @param array $context
     */
    public function alert($message, array $context = []): void
    {
        $this->channel()->alert($message, $context);
    }
    
    /**
     * Critical conditions
     * 
     * @param string $message
     * @param array $context
     */
    public function critical($message, array $context = []): void
    {
        $this->channel()->critical($message, $context);
    }
    
    /**
     * Runtime errors that do not require immediate action
     * 
     * @param string $message
     * @param array $context
     */
    public function error($message, array $context = []): void
    {
        $this->channel()->error($message, $context);
    }
    
    /**
     * Exceptional occurrences that are not errors
     * 
     * @param string $message
     * @param array $context
     */
    public function warning($message, array $context = []): void
    {
        $this->channel()->warning($message, $context);
    }
    
    /**
     * Normal but significant events
     * 
     * @param string $message
     * @param array $context
     */
    public function notice($message, array $context = []): void
    {
        $this->channel()->notice($message, $context);
    }
    
    /**
     * Interesting events
     * 
     * @param string $message
     * @param array $context
     */
    public function info($message, array $context = []): void
    {
        $this->channel()->info($message, $context);
    }
    
    /**
     * Detailed debug information
     * 
     * @param string $message
     * @param array $context
     */
    public function debug($message, array $context = []): void
    {
        $this->channel()->debug($message, $context);
    }
    
    /**
     * Creates a structured log entry
     * 
     * @param string $level
     * @param string $message
     * @param array $data
     * @param array $context
     */
    public function logStructured(string $level, string $message, array $data, array $context = []): void
    {
        $this->channel()->logStructured($level, $message, $data, $context);
    }
    
    /**
     * Dynamically call methods on the default channel
     * 
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return $this->channel()->$method(...$parameters);
    }
} 