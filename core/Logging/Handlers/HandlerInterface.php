<?php

namespace Portfolion\Logging\Handlers;

use Portfolion\Logging\Formatters\FormatterInterface;

/**
 * Interface for log handlers
 */
interface HandlerInterface
{
    /**
     * Handles a log record
     * 
     * @param array $record
     * @return bool Whether the record was handled
     */
    public function handle(array $record): bool;
    
    /**
     * Handles a batch of log records
     * 
     * @param array $records
     * @return void
     */
    public function handleBatch(array $records): void;
    
    /**
     * Check if the handler handles the given log level
     * 
     * @param string $level
     * @return bool
     */
    public function isHandling(string $level): bool;
    
    /**
     * Add a processor to the handler
     * 
     * @param callable $processor
     * @return self
     */
    public function pushProcessor(callable $processor): self;
    
    /**
     * Set the formatter for this handler
     * 
     * @param FormatterInterface $formatter
     * @return self
     */
    public function setFormatter(FormatterInterface $formatter): self;
    
    /**
     * Get the formatter for this handler
     * 
     * @return FormatterInterface
     */
    public function getFormatter(): FormatterInterface;
} 