<?php

namespace Portfolion\Logging\Handlers;

use InvalidArgumentException;
use RuntimeException;

/**
 * Logs records to a file
 */
class FileHandler extends AbstractHandler
{
    /**
     * @var string The log file path
     */
    protected string $path;
    
    /**
     * @var resource|null The file handle
     */
    protected $stream;
    
    /**
     * @var int|null The file permission
     */
    protected ?int $filePermission;
    
    /**
     * @var bool Whether to use file locking when writing
     */
    protected bool $useLocking;
    
    /**
     * @var string|null The directory path
     */
    protected ?string $dirName = null;
    
    /**
     * Constructor
     * 
     * @param string $path The log file path
     * @param string $level The minimum logging level
     * @param bool $bubble Whether the messages that are handled can bubble up the stack
     * @param int|null $filePermission Optional file permissions (default: 0644)
     * @param bool $useLocking Try to lock log file before writing
     */
    public function __construct(
        string $path,
        string $level = 'DEBUG',
        bool $bubble = true,
        ?int $filePermission = null,
        bool $useLocking = false
    ) {
        parent::__construct($level, $bubble);
        
        $this->path = $path;
        $this->filePermission = $filePermission;
        $this->useLocking = $useLocking;
    }
    
    /**
     * Destructor - closes the stream when the handler is destroyed
     */
    public function __destruct()
    {
        if ($this->stream !== null) {
            fclose($this->stream);
            $this->stream = null;
        }
    }
    
    /**
     * Opens the stream if it's not already open
     * 
     * @throws RuntimeException If the stream cannot be opened
     */
    protected function openStream(): void
    {
        if ($this->stream !== null) {
            return;
        }
        
        $this->createDir();
        
        $this->stream = fopen($this->path, 'a');
        if ($this->stream === false) {
            throw new RuntimeException("Unable to open log file at path: {$this->path}");
        }
        
        if ($this->filePermission !== null) {
            @chmod($this->path, $this->filePermission);
        }
    }
    
    /**
     * Creates the directory for the log file if it doesn't exist
     * 
     * @throws RuntimeException If the directory cannot be created
     */
    protected function createDir(): void
    {
        // Build the directory path if it hasn't been cached
        if ($this->dirName === null) {
            $this->dirName = dirname($this->path);
        }
        
        // Create the directory if it doesn't exist
        if (!is_dir($this->dirName)) {
            $status = mkdir($this->dirName, 0755, true);
            if (!$status && !is_dir($this->dirName)) {
                throw new RuntimeException("Unable to create directory for log file: {$this->dirName}");
            }
        }
        
        // Check if the directory is writable
        if (!is_writable($this->dirName)) {
            throw new RuntimeException("Log directory is not writable: {$this->dirName}");
        }
    }
    
    /**
     * Writes a log record to the file
     * 
     * @param array $record The log record
     */
    protected function write(array $record): void
    {
        $this->openStream();
        
        $formatted = $this->getFormatter()->format($record);
        
        if ($this->useLocking) {
            // Acquire an exclusive lock
            flock($this->stream, LOCK_EX);
            
            // Write to the stream
            fwrite($this->stream, $formatted);
            
            // Release the lock
            flock($this->stream, LOCK_UN);
        } else {
            // Write without locking
            fwrite($this->stream, $formatted);
        }
    }
    
    /**
     * Writes a batch of log records to the file
     * 
     * @param array $records The log records
     */
    protected function writeBatch(array $records): void
    {
        $this->openStream();
        
        $formatted = $this->getFormatter()->formatBatch($records);
        
        if ($this->useLocking) {
            // Acquire an exclusive lock
            flock($this->stream, LOCK_EX);
            
            // Write to the stream
            fwrite($this->stream, $formatted);
            
            // Release the lock
            flock($this->stream, LOCK_UN);
        } else {
            // Write without locking
            fwrite($this->stream, $formatted);
        }
    }
} 