<?php

namespace Portfolion\Logging\Formatters;

/**
 * Formats log records into a line of text
 */
class LineFormatter implements FormatterInterface
{
    /**
     * @var string The format of the output
     */
    protected string $format;
    
    /**
     * @var string The date format
     */
    protected string $dateFormat;
    
    /**
     * @var bool Whether to allow inline line breaks
     */
    protected bool $allowInlineLineBreaks;
    
    /**
     * @var bool Whether to ignore empty context and extra data
     */
    protected bool $ignoreEmptyContextAndExtra;
    
    /**
     * @var bool Whether to include microseconds in the date
     */
    protected bool $includeMicroseconds;
    
    /**
     * Default format for log lines
     */
    protected const DEFAULT_FORMAT = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
    
    /**
     * Default date format
     */
    protected const DEFAULT_DATE_FORMAT = 'Y-m-d H:i:s';
    
    /**
     * Constructor
     * 
     * @param string|null $format The format of the output
     * @param string|null $dateFormat The date format
     * @param bool $allowInlineLineBreaks Whether to allow inline line breaks
     * @param bool $ignoreEmptyContextAndExtra Whether to ignore empty context and extra data
     */
    public function __construct(
        ?string $format = null,
        ?string $dateFormat = null,
        bool $allowInlineLineBreaks = false,
        bool $ignoreEmptyContextAndExtra = false
    ) {
        $this->format = $format ?? static::DEFAULT_FORMAT;
        $this->dateFormat = $dateFormat ?? static::DEFAULT_DATE_FORMAT;
        $this->allowInlineLineBreaks = $allowInlineLineBreaks;
        $this->ignoreEmptyContextAndExtra = $ignoreEmptyContextAndExtra;
        $this->includeMicroseconds = strpos($this->dateFormat, 'u') !== false;
    }
    
    /**
     * Formats a log record
     * 
     * @param array $record A log record to format
     * @return string The formatted record
     */
    public function format(array $record): string
    {
        $output = $this->format;
        
        // Replace datetime placeholder
        if (isset($record['datetime']) && $record['datetime'] instanceof \DateTimeInterface) {
            $output = str_replace(
                '%datetime%',
                $record['datetime']->format($this->dateFormat),
                $output
            );
        }
        
        // Replace other placeholders
        $output = str_replace(
            [
                '%channel%',
                '%level_name%',
                '%message%',
            ],
            [
                $record['channel'] ?? '',
                $record['level_name'] ?? '',
                $record['message'] ?? '',
            ],
            $output
        );
        
        // Replace context
        $context = $this->normalizeData($record['context'] ?? []);
        if ($this->ignoreEmptyContextAndExtra && empty($context)) {
            $output = str_replace('%context%', '', $output);
        } else {
            $output = str_replace('%context%', $this->stringify($context), $output);
        }
        
        // Replace extra
        $extra = $this->normalizeData($record['extra'] ?? []);
        if ($this->ignoreEmptyContextAndExtra && empty($extra)) {
            $output = str_replace('%extra%', '', $output);
        } else {
            $output = str_replace('%extra%', $this->stringify($extra), $output);
        }
        
        return $output;
    }
    
    /**
     * Formats a batch of log records
     * 
     * @param array $records A batch of log records to format
     * @return string The formatted records
     */
    public function formatBatch(array $records): string
    {
        $message = '';
        
        foreach ($records as $record) {
            $message .= $this->format($record);
        }
        
        return $message;
    }
    
    /**
     * Normalizes data for string conversion
     * 
     * @param mixed $data
     * @return mixed
     */
    protected function normalizeData($data)
    {
        if (null === $data || is_scalar($data)) {
            return $data;
        }
        
        if (is_array($data) || $data instanceof \Traversable) {
            $normalized = [];
            
            foreach ($data as $key => $value) {
                $normalized[$key] = $this->normalizeData($value);
            }
            
            return $normalized;
        }
        
        if ($data instanceof \DateTimeInterface) {
            return $data->format($this->dateFormat);
        }
        
        if (is_object($data)) {
            // If the object has a __toString method, use it
            if (method_exists($data, '__toString')) {
                return (string) $data;
            }
            
            // Otherwise, return the class name
            return sprintf('[object %s]', get_class($data));
        }
        
        if (is_resource($data)) {
            return sprintf('[resource %s]', get_resource_type($data));
        }
        
        return '[unknown ' . gettype($data) . ']';
    }
    
    /**
     * Converts a PHP value to a string
     * 
     * @param mixed $data
     * @return string
     */
    protected function stringify($data): string
    {
        if (null === $data || is_bool($data)) {
            return var_export($data, true);
        }
        
        if (is_scalar($data)) {
            return (string) $data;
        }
        
        if (is_array($data)) {
            if (count($data) === 0) {
                return '[]';
            }
            
            $jsonFlags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
            if ($this->allowInlineLineBreaks) {
                $jsonFlags |= JSON_PRETTY_PRINT;
            }
            
            return json_encode($data, $jsonFlags);
        }
        
        return '[' . gettype($data) . ']';
    }
} 