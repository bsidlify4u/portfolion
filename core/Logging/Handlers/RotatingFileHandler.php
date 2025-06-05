<?php

namespace Portfolion\Logging\Handlers;

/**
 * Logs records to a file, rotating files by day
 */
class RotatingFileHandler extends FileHandler
{
    /**
     * @var int Number of days to keep logs
     */
    protected int $maxFiles;
    
    /**
     * @var string|null The file path pattern
     */
    protected ?string $filenamePattern;
    
    /**
     * @var string|null The date format
     */
    protected ?string $dateFormat;
    
    /**
     * @var int|null The timestamp of the current log file
     */
    protected ?int $currentLogTimestamp = null;
    
    /**
     * Constructor
     * 
     * @param string $path The log file path
     * @param int $maxFiles The maximum number of files to keep (0 means unlimited)
     * @param string $level The minimum logging level
     * @param bool $bubble Whether the messages that are handled can bubble up the stack
     * @param int|null $filePermission Optional file permissions (default: 0644)
     * @param bool $useLocking Try to lock log file before writing
     */
    public function __construct(
        string $path,
        int $maxFiles = 0,
        string $level = 'DEBUG',
        bool $bubble = true,
        ?int $filePermission = null,
        bool $useLocking = false
    ) {
        parent::__construct($path, $level, $bubble, $filePermission, $useLocking);
        
        $this->maxFiles = $maxFiles;
        $this->filenamePattern = $this->getFilenamePattern($path);
        $this->dateFormat = 'Y-m-d';
    }
    
    /**
     * Get the filename pattern based on the path
     * 
     * @param string $path Log file path
     * @return string
     */
    protected function getFilenamePattern(string $path): string
    {
        $info = pathinfo($path);
        
        $dirname = $info['dirname'] ?? '';
        $basename = $info['basename'] ?? '';
        $extension = isset($info['extension']) ? ('.' . $info['extension']) : '';
        $filename = basename($basename, $extension);
        
        return $dirname . '/' . $filename . '-%s' . $extension;
    }
    
    /**
     * Opens the stream if it's not already open
     * 
     * @throws RuntimeException If the stream cannot be opened
     */
    protected function openStream(): void
    {
        // Get current date
        $currentTimestamp = time();
        $currentDate = date($this->dateFormat, $currentTimestamp);
        
        // Check if we need to rotate the file
        if ($this->currentLogTimestamp !== null && date($this->dateFormat, $this->currentLogTimestamp) !== $currentDate) {
            // Close current stream
            if ($this->stream !== null) {
                fclose($this->stream);
                $this->stream = null;
            }
        }
        
        // Update current timestamp
        $this->currentLogTimestamp = $currentTimestamp;
        
        // Get the real path to open
        $rotatedPath = $this->getRotatedPath($currentDate);
        
        // Set the current path and open the stream
        $this->path = $rotatedPath;
        
        // Call parent to open the stream
        parent::openStream();
        
        // Perform cleanup if needed
        if ($this->maxFiles > 0) {
            $this->cleanup();
        }
    }
    
    /**
     * Get the path for the current date
     * 
     * @param string $date Current date formatted with dateFormat
     * @return string
     */
    protected function getRotatedPath(string $date): string
    {
        return sprintf($this->filenamePattern, $date);
    }
    
    /**
     * Cleanup old log files
     */
    protected function cleanup(): void
    {
        // Get the pattern for matching old log files
        $fileInfo = pathinfo($this->filenamePattern);
        $glob = str_replace('%s', '*', $this->filenamePattern);
        
        // Get all log files
        $logFiles = glob($glob);
        
        if (count($logFiles) <= $this->maxFiles) {
            return;
        }
        
        // Sort files by name (which will be by date for our format)
        usort($logFiles, function($a, $b) {
            return strcmp($b, $a);
        });
        
        // Remove older files that exceed the maxFiles limit
        foreach (array_slice($logFiles, $this->maxFiles) as $file) {
            if (is_writable($file)) {
                unlink($file);
            }
        }
    }
} 