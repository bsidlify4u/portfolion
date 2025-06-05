<?php
namespace Core\Security;

use RuntimeException;

class ValidationException extends RuntimeException {
    /** @var array<string, array<string>> */
    protected array $errors;

    /**
     * @param array<string, array<string>> $errors
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(array $errors, string $message = "The given data was invalid.", int $code = 422, ?\Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    /**
     * Get validation errors.
     *
     * @return array<string, array<string>>
     */
    public function errors(): array {
        return $this->errors;
    }
}
