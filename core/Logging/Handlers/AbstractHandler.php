<?php

namespace Portfolion\Logging\Handlers;

use Portfolion\Logging\Formatters\FormatterInterface;
use Portfolion\Logging\Formatters\LineFormatter;
use Psr\Log\LogLevel;
use InvalidArgumentException;

/**
 * Base handler class providing common handler functionality
 */
abstract class AbstractHandler implements HandlerInterface
{
    /**
     * @var string The minimum logging level at which this handler will be triggered
     */
    protected string $level;
    
    /**
     * @var bool Whether the handler should bubble up to other handlers
     */
    protected bool $bubble;
    
    /**
     * @var FormatterInterface|null The formatter instance
     */
    protected ?FormatterInterface $formatter = null;
    
    /**
     * @var array The processors registered with this handler
     */
    protected array $processors = [];
    
    /**
     * PSR Log levels severity map
     */
    protected const LOG_LEVELS_SEVERITY = [
        LogLevel::DEBUG => 0,
        LogLevel::INFO => 1,
        LogLevel::NOTICE => 2,
        LogLevel::WARNING => 3,
        LogLevel::ERROR => 4,
        LogLevel::CRITICAL => 5,
        LogLevel::ALERT => 6,
        LogLevel::EMERGENCY => 7,
    ];
    
    /**
     * Constructor
     *
     * @param string $level The minimum logging level at which this handler will be triggered
     * @param bool $bubble Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct(string $level = LogLevel::DEBUG, bool $bubble = true)
    {
        $this->level = $level;
        $this->bubble = $bubble;
    }
    
    /**
     * Handles a log record
     * 
     * @param array $record The record to handle
     * @return bool Whether the record was handled
     */
    public function handle(array $record): bool
    {
        if (!$this->isHandling($record['level'])) {
            return false;
        }
        
        // Process the record
        $record = $this->processRecord($record);
        
        // Write the record
        $this->write($record);
        
        return !$this->bubble;
    }
    
    /**
     * Handles a batch of log records
     * 
     * @param array $records The records to handle
     */
    public function handleBatch(array $records): void
    {
        $filteredRecords = [];
        
        foreach ($records as $record) {
            if ($this->isHandling($record['level'])) {
                $filteredRecords[] = $this->processRecord($record);
            }
        }
        
        if (!empty($filteredRecords)) {
            $this->writeBatch($filteredRecords);
        }
    }
    
    /**
     * Check if the handler handles the given log level
     * 
     * @param string $level
     * @return bool
     */
    public function isHandling(string $level): bool
    {
        $levelSeverity = self::LOG_LEVELS_SEVERITY[$level] ?? 0;
        $handlerSeverity = self::LOG_LEVELS_SEVERITY[$this->level] ?? 0;
        
        return $levelSeverity >= $handlerSeverity;
    }
    
    /**
     * Adds a processor to the handler
     * 
     * @param callable $processor
     * @return self
     */
    public function pushProcessor(callable $processor): self
    {
        array_unshift($this->processors, $processor);
        
        return $this;
    }
    
    /**
     * Removes the processor on top of the stack and returns it
     * 
     * @return callable
     * @throws \LogicException If no processors are defined
     */
    public function popProcessor(): callable
    {
        if (empty($this->processors)) {
            throw new \LogicException('You tried to pop from an empty processor stack.');
        }
        
        return array_shift($this->processors);
    }
    
    /**
     * Sets the formatter
     * 
     * @param FormatterInterface $formatter
     * @return self
     */
    public function setFormatter(FormatterInterface $formatter): self
    {
        $this->formatter = $formatter;
        
        return $this;
    }
    
    /**
     * Gets the formatter
     * 
     * @return FormatterInterface
     */
    public function getFormatter(): FormatterInterface
    {
        if ($this->formatter === null) {
            $this->formatter = $this->getDefaultFormatter();
        }
        
        return $this->formatter;
    }
    
    /**
     * Creates the default formatter if none is set
     * 
     * @return FormatterInterface
     */
    protected function getDefaultFormatter(): FormatterInterface
    {
        return new LineFormatter();
    }
    
    /**
     * Processes a record
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
     * Writes a log record
     * 
     * @param array $record
     * @return void
     */
    abstract protected function write(array $record): void;
    
    /**
     * Writes a batch of log records
     * 
     * @param array $records
     * @return void
     */
    protected function writeBatch(array $records): void
    {
        foreach ($records as $record) {
            $this->write($record);
        }
    }
} 