<?php
namespace Core\Auth;

use RuntimeException;

class AuthenticationException extends RuntimeException {
    /**
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 401, ?\Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
