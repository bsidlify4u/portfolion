<?php
namespace Portfolion\Security;

class SecurityMiddleware {
    private static array $validators = [];
    private static string $csrfToken;
    
    public static function init(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Generate CSRF token if not exists
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        self::$csrfToken = $_SESSION['csrf_token'];
    }
    
    public static function getCsrfToken(): string {
        return self::$csrfToken;
    }
    
    public static function validateCsrfToken(?string $token): bool {
        if (!$token || !hash_equals($_SESSION['csrf_token'], $token)) {
            throw new \RuntimeException('CSRF token validation failed');
        }
        return true;
    }
    
    public static function sanitize($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    
    public static function addValidator(string $field, callable $validator): void {
        self::$validators[$field] = $validator;
    }
    
    public static function validate(array $data): array {
        $errors = [];
        foreach (self::$validators as $field => $validator) {
            if (isset($data[$field])) {
                try {
                    $validator($data[$field]);
                } catch (\Exception $e) {
                    $errors[$field] = $e->getMessage();
                }
            }
        }
        return $errors;
    }
}
