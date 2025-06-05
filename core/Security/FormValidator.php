<?php
namespace Portfolion\Security;

use Portfolion\Database\QueryBuilder;

class FormValidator {
    protected array $errors = [];
    protected array $data;
    protected array $rules;
    protected array $messages;
    protected ?QueryBuilder $db;
    
    /**
     * Create a new validator instance.
     *
     * @param array<string, mixed> $data
     * @param array<string, string|array<string>> $rules
     * @param array<string, string> $messages
     * @param QueryBuilder|null $db
     */
    public function __construct(array $data, array $rules, array $messages = [], ?QueryBuilder $db = null) {
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = $messages;
        $this->db = $db;
    }
    
    /**
     * Create a new validator instance.
     *
     * @param array<string, mixed> $data
     * @param array<string, string|array<string>> $rules
     * @param array<string, string> $messages
     * @return self
     */
    public static function make(array $data, array $rules, array $messages = []): self {
        return new self($data, $rules, $messages);
    }
    
    /**
     * Determine if validation fails.
     *
     * @return bool
     */
    public function fails(): bool {
        return !$this->validate();
    }
    
    /**
     * Get validation errors.
     *
     * @return array<string, array<string>>
     */
    public function getErrors(): array {
        return $this->errors;
    }
    
    /**
     * Validate the data against the rules.
     *
     * @return bool
     */
    public function validate(): bool {
        $this->errors = [];
        
        foreach ($this->rules as $field => $rules) {
            $value = $this->getValue($field);
            $rules = is_string($rules) ? explode('|', $rules) : $rules;
            
            foreach ($rules as $rule) {
                $this->validateRule($field, $value, $rule);
            }
        }
        
        return empty($this->errors);
    }
    
    /**
     * Get the value of a field.
     *
     * @param string $field
     * @return mixed
     */
    protected function getValue(string $field): mixed {
        $keys = explode('.', $field);
        $value = $this->data;
        
        foreach ($keys as $key) {
            if (!is_array($value) || !isset($value[$key])) {
                return null;
            }
            $value = $value[$key];
        }
        
        return $value;
    }
    
    /**
     * Add an error message.
     *
     * @param string $field
     * @param string $rule
     * @param array<string> $parameters
     */
    protected function addError(string $field, string $rule, array $parameters = []): void {
        $message = $this->messages["$field.$rule"] 
            ?? $this->messages[$field] 
            ?? $this->getDefaultMessage($field, $rule, $parameters);
            
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        
        $this->errors[$field][] = $this->replaceParameters($message, $parameters);
    }
    
    /**
     * Get the default error message for a rule.
     *
     * @param string $field
     * @param string $rule
     * @param array<string> $parameters
     * @return string
     */
    protected function getDefaultMessage(string $field, string $rule, array $parameters): string {
        $field = str_replace('_', ' ', $field);
        
        return match($rule) {
            'required' => "The $field field is required.",
            'email' => "The $field must be a valid email address.",
            'min' => "The $field must be at least {$parameters[0]} characters.",
            'max' => "The $field may not be greater than {$parameters[0]} characters.",
            'unique' => "The $field has already been taken.",
            'confirmed' => "The $field confirmation does not match.",
            default => "The $field field is invalid."
        };
    }
    
    /**
     * Replace parameters in the error message.
     *
     * @param string $message
     * @param array<string> $parameters
     * @return string
     */
    protected function replaceParameters(string $message, array $parameters): string {
        foreach ($parameters as $i => $parameter) {
            $message = str_replace("{{$i}}", $parameter, $message);
        }
        return $message;
    }
    
    /**
     * Validate that a field exists.
     *
     * @param string $field
     * @param mixed $value
     * @return bool
     */
    protected function validateRequired(string $field, mixed $value): bool {
        if (is_null($value)) {
            return false;
        } elseif (is_string($value) && trim($value) === '') {
            return false;
        } elseif (is_array($value) && count($value) < 1) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate that a field is unique in the database.
     *
     * @param string $field
     * @param mixed $value
     * @param array<string> $parameters
     * @return bool
     */
    protected function validateUnique(string $field, mixed $value, array $parameters): bool {
        if (!$this->db) {
            throw new \RuntimeException('Database connection not provided for unique validation');
        }
        
        [$table, $column] = count($parameters) > 1 
            ? [$parameters[0], $parameters[1]] 
            : [$parameters[0], $field];
            
        $query = $this->db->table($table)->where($column, '=', $value);
        
        if (isset($parameters[2], $parameters[3])) {
            $query->where($parameters[2], '!=', $parameters[3]);
        }
        
        return !$query->exists();
    }
    
    protected function validateEmail($field, $value): bool {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    protected function validateMin($field, $value, array $parameters): bool {
        $min = $parameters[0] ?? 0;
        
        if (is_numeric($value)) {
            return $value >= $min;
        }
        
        return mb_strlen($value) >= $min;
    }
    
    protected function validateMax($field, $value, array $parameters): bool {
        $max = $parameters[0] ?? 0;
        
        if (is_numeric($value)) {
            return $value <= $max;
        }
        
        return mb_strlen($value) <= $max;
    }
    
    protected function validateBetween($field, $value, array $parameters): bool {
        [$min, $max] = $parameters;
        
        if (is_numeric($value)) {
            return $value >= $min && $value <= $max;
        }
        
        $length = mb_strlen($value);
        return $length >= $min && $length <= $max;
    }
    
    protected function validateNumeric($field, $value): bool {
        return is_numeric($value);
    }
    
    protected function validateInteger($field, $value): bool {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }
    
    protected function validateString($field, $value): bool {
        return is_string($value);
    }
    
    protected function validateArray($field, $value): bool {
        return is_array($value);
    }
    
    protected function validateUrl($field, $value): bool {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
    
    protected function validateIp($field, $value): bool {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }
    
    protected function validateDate($field, $value): bool {
        if ($value instanceof \DateTime) {
            return true;
        }
        
        try {
            new \DateTime($value);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    protected function validateRegex($field, $value, array $parameters): bool {
        return preg_match($parameters[0], $value) > 0;
    }
}
