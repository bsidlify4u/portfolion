<?php
namespace Portfolion\Security;

use Portfolion\Config;

class SecurityServiceProvider {
    private static bool $initialized = false;
    private static Config $config;

    public static function init(): void {
        if (self::$initialized) {
            return;
        }

        self::$config = Config::getInstance();
        self::configureSecurityHeaders();
        self::$initialized = true;
    }

    /**
     * Hash a password using secure algorithm.
     *
     * @param string $password
     * @return string
     */
    public static function hashPassword(string $password): string {
        $algo = self::$config->get('security.password_algo', PASSWORD_ARGON2ID);
        $options = self::$config->get('security.password_options', [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);

        return password_hash($password, $algo, $options);
    }

    /**
     * Verify a password against a hash.
     *
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }

    /**
     * Generate a cryptographically secure random token.
     *
     * @param int $length
     * @return string
     */
    public static function generateRandomToken(int $length = 32): string {
        return bin2hex(random_bytes($length));
    }

    /**
     * Sanitize input data.
     *
     * @param string|array<mixed> $input
     * @return string|array<string|array<mixed>>
     */
    public static function sanitize(string|array $input): string|array {
        if (is_array($input)) {
            /** @var array<string|array<mixed>> */
            return array_map(fn($value) => self::sanitize($value), $input);
        }

        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private static function configureSecurityHeaders(): void {
        $headers = [
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-XSS-Protection' => '1; mode=block',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Content-Security-Policy' => self::buildCSP(),
            'Permissions-Policy' => self::buildPermissionsPolicy(),
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains'
        ];

        foreach ($headers as $header => $value) {
            if (!headers_sent()) {
                header("$header: $value");
            }
        }
    }

    private static function buildCSP(): string {
        $policies = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https:",
            "font-src 'self'",
            "connect-src 'self'",
            "media-src 'self'",
            "object-src 'none'",
            "frame-src 'self'",
            "base-uri 'self'",
            "form-action 'self'"
        ];

        return implode('; ', $policies);
    }

    private static function buildPermissionsPolicy(): string {
        $policies = [
            'accelerometer=()',
            'camera=()',
            'geolocation=()',
            'gyroscope=()',
            'magnetometer=()',
            'microphone=()',
            'payment=()',
            'usb=()'
        ];

        return implode(', ', $policies);
    }

    public static function validateInput($input, array $rules): array {
        $errors = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = $input[$field] ?? null;
            $fieldRules = explode('|', $fieldRules);
            
            foreach ($fieldRules as $rule) {
                if (strpos($rule, ':') !== false) {
                    [$ruleName, $ruleValue] = explode(':', $rule);
                } else {
                    $ruleName = $rule;
                    $ruleValue = null;
                }
                
                $error = self::validateRule($field, $value, $ruleName, $ruleValue);
                if ($error) {
                    $errors[$field][] = $error;
                }
            }
        }
        
        return $errors;
    }

    private static function validateRule(string $field, $value, string $rule, $ruleValue = null): ?string {
        switch ($rule) {
            case 'required':
                if (empty($value)) {
                    return "The $field field is required.";
                }
                break;
                
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return "The $field must be a valid email address.";
                }
                break;
                
            case 'min':
                if (strlen($value) < $ruleValue) {
                    return "The $field must be at least $ruleValue characters.";
                }
                break;
                
            case 'max':
                if (strlen($value) > $ruleValue) {
                    return "The $field may not be greater than $ruleValue characters.";
                }
                break;
                
            case 'numeric':
                if (!is_numeric($value)) {
                    return "The $field must be a number.";
                }
                break;
                
            case 'alpha':
                if (!ctype_alpha($value)) {
                    return "The $field must only contain letters.";
                }
                break;
                
            case 'alphanumeric':
                if (!ctype_alnum($value)) {
                    return "The $field must only contain letters and numbers.";
                }
                break;
        }
        
        return null;
    }
}
