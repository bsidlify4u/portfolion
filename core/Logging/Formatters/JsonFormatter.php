<?php

namespace Portfolion\Logging\Formatters;

/**
 * Formats log records as JSON
 */
class JsonFormatter implements FormatterInterface
{
    /**
     * Batch mode constants
     */
    public const BATCH_MODE_JSON = 1;
    public const BATCH_MODE_NEWLINES = 2;
    
    /**
     * @var int The batch mode
     */
    protected int $batchMode;
    
    /**
     * @var bool Whether to append a newline after each record
     */
    protected bool $appendNewline;
    
    /**
     * @var bool Whether to ignore empty arrays in the record
     */
    protected bool $ignoreEmptyContextAndExtra;
    
    /**
     * @var int Bit mask of JSON constants
     */
    protected int $jsonFlags;
    
    /**
     * Constructor
     * 
     * @param int $batchMode The batch mode
     * @param bool $appendNewline Whether to append a newline after each record
     * @param bool $ignoreEmptyContextAndExtra Whether to ignore empty arrays in the record
     * @param int $jsonFlags Bit mask of JSON constants (JSON_UNESCAPED_SLASHES, JSON_PRETTY_PRINT, etc.)
     */
    public function __construct(
        int $batchMode = self::BATCH_MODE_JSON,
        bool $appendNewline = true,
        bool $ignoreEmptyContextAndExtra = false,
        int $jsonFlags = 0
    ) {
        $this->batchMode = $batchMode;
        $this->appendNewline = $appendNewline;
        $this->ignoreEmptyContextAndExtra = $ignoreEmptyContextAndExtra;
        $this->jsonFlags = $jsonFlags;
    }
    
    /**
     * Formats a log record
     * 
     * @param array $record A log record to format
     * @return string The formatted record
     */
    public function format(array $record): string
    {
        $normalized = $this->normalize($record);
        
        if (isset($normalized['datetime']) && $normalized['datetime'] instanceof \DateTimeInterface) {
            $normalized['datetime'] = $normalized['datetime']->format('c');
        }
        
        if ($this->ignoreEmptyContextAndExtra) {
            if (empty($normalized['context'])) {
                unset($normalized['context']);
            }
            
            if (empty($normalized['extra'])) {
                unset($normalized['extra']);
            }
        }
        
        $json = json_encode($normalized, $this->jsonFlags);
        
        if ($json === false) {
            $json = $this->handleJsonError(json_last_error(), $normalized);
        }
        
        if ($this->appendNewline) {
            return $json . "\n";
        }
        
        return $json;
    }
    
    /**
     * Formats a batch of log records
     * 
     * @param array $records A batch of log records to format
     * @return string The formatted records
     */
    public function formatBatch(array $records): string
    {
        switch ($this->batchMode) {
            case self::BATCH_MODE_NEWLINES:
                $output = '';
                
                foreach ($records as $record) {
                    $output .= $this->format($record);
                }
                
                return $output;
                
            case self::BATCH_MODE_JSON:
            default:
                $normalized = [];
                
                foreach ($records as $record) {
                    $normalized[] = $this->normalize($record);
                }
                
                $json = json_encode($normalized, $this->jsonFlags);
                
                if ($json === false) {
                    $json = $this->handleJsonError(json_last_error(), $normalized);
                }
                
                if ($this->appendNewline) {
                    return $json . "\n";
                }
                
                return $json;
        }
    }
    
    /**
     * Normalizes data for JSON encoding
     * 
     * @param mixed $data
     * @return mixed
     */
    protected function normalize($data)
    {
        if (null === $data || is_scalar($data)) {
            return $data;
        }
        
        if (is_array($data) || $data instanceof \Traversable) {
            $normalized = [];
            
            foreach ($data as $key => $value) {
                $normalized[$key] = $this->normalize($value);
            }
            
            return $normalized;
        }
        
        if ($data instanceof \DateTimeInterface) {
            return $data->format('c');
        }
        
        if (is_object($data)) {
            // If the object has a __toString method, use it
            if (method_exists($data, '__toString')) {
                return (string) $data;
            }
            
            // If the object can be serialized to JSON, do that
            if ($data instanceof \JsonSerializable) {
                return $data;
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
     * Handle a JSON encoding error
     * 
     * @param int $code JSON error code
     * @param mixed $data Data that caused the error
     * @return string JSON-encoded error message
     */
    protected function handleJsonError(int $code, $data): string
    {
        $error = match ($code) {
            JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH => 'Underflow or the modes mismatch',
            JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
            JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
            JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded',
            default => 'Unknown error',
        };
        
        return json_encode([
            'error' => $error,
            'data' => 'Error encoding log record',
        ]);
    }
} 