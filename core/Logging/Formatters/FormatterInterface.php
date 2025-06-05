<?php

namespace Portfolion\Logging\Formatters;

/**
 * Interface for log formatters
 */
interface FormatterInterface
{
    /**
     * Formats a log record
     * 
     * @param array $record
     * @return string|array The formatted record
     */
    public function format(array $record);
    
    /**
     * Formats a batch of log records
     * 
     * @param array $records
     * @return string|array The formatted records
     */
    public function formatBatch(array $records);
} 