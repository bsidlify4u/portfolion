<?php

namespace Portfolion\Logging;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Portfolion\Logging\Handlers\HandlerInterface;
use DateTimeImmutable;

/**
 * Logger for a specific channel
 */
class ChannelLogger implements LoggerInterface
{
    /**
     * @var string The channel name
     */
    protected string $channel;
    
    /**
     * @var HandlerInterface The handler for this channel
     */
    protected HandlerInterface $handler;
    
    /**
     * @var array The processors for this channel
     */
    protected array $processors = [];
    
    /**
     * Create a new channel logger
     * 
     * @param string $channel
     * @param HandlerInterface $handler
     * @param array $processors
     */
    public function __construct(string $channel, HandlerInterface $handler, array $processors = [])
    {
        $this->channel = $channel;
        $this->handler = $handler;
        $this->processors = $processors;
    }
    
    /**
     * System is unusable
     * 
     * @param string $message
     * @param array $context
     */
    public function emergency($message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }
    
    /**
     * Action must be taken immediately
     * 
     * @param string $message
     * @param array $context
     */
    public function alert($message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }
    
    /**
     * Critical conditions
     * 
     * @param string $message
     * @param array $context
     */
    public function critical($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }
    
    /**
     * Runtime errors that do not require immediate action
     * 
     * @param string $message
     * @param array $context
     */
    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }
    
    /**
     * Exceptional occurrences that are not errors
     * 
     * @param string $message
     * @param array $context
     */
    public function warning($message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }
    
    /**
     * Normal but significant events
     * 
     * @param string $message
     * @param array $context
     */
    public function notice($message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }
    
    /**
     * Interesting events
     * 
     * @param string $message
     * @param array $context
     */
    public function info($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }
    
    /**
     * Detailed debug information
     * 
     * @param string $message
     * @param array $context
     */
    public function debug($message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }
    
    /**
     * Logs with an arbitrary level
     * 
     * @param mixed $level
     * @param string $message
     * @param array $context
     */
    public function log($level, $message, array $context = []): void
    {
        $record = [
            'message' => $this->interpolate($message, $context),
            'context' => $context,
            'level' => $level,
            'level_name' => strtoupper($level),
            'channel' => $this->channel,
            'datetime' => new DateTimeImmutable(),
            'extra' => [],
        ];
        
        // Process the record through all processors
        $record = $this->processRecord($record);
        
        // Handle the record
        $this->handler->handle($record);
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
        $structuredContext = array_merge(['data' => $data], $context);
        $this->log($level, $message, $structuredContext);
    }
    
    /**
     * Add context to all future logs
     * 
     * @param array $context
     * @return $this
     */
    public function withContext(array $context): self
    {
        $this->pushProcessor(function (array $record) use ($context) {
            $record['context'] = array_merge($record['context'], $context);
            return $record;
        });
        
        return $this;
    }
    
    /**
     * Add a processor to the logger
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
     * Process a record through all processors
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
     * Interpolate context values into message placeholders
     * 
     * @param string $message
     * @param array $context
     * @return string
     */
    protected function interpolate(string $message, array $context): string
    {
        $replace = [];
        
        foreach ($context as $key => $value) {
            // Skip objects that can't be cast to string
            if (is_object($value) && !method_exists($value, '__toString')) {
                continue;
            }
            
            // Skip arrays and other non-stringable types
            if (!is_scalar($value) && !is_null($value)) {
                continue;
            }
            
            $replace['{' . $key . '}'] = $value;
        }
        
        return strtr($message, $replace);
    }
} 