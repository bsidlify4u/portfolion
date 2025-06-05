<?php

namespace Portfolion\Error;

/**
 * Exception for security-related errors
 */
class SecurityException extends \Exception
{
    /**
     * @var int HTTP status code
     */
    protected $statusCode = 403;
    
    /**
     * Create a new security exception
     * 
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous exception
     */
    public function __construct(string $message = 'Security violation', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * Get the HTTP status code
     * 
     * @return int The HTTP status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
    
    /**
     * Set the HTTP status code
     * 
     * @param int $statusCode The HTTP status code
     * @return self
     */
    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }
} 