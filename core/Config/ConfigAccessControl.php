<?php

namespace Portfolion\Config;

/**
 * Manages access control for configuration values
 */
class ConfigAccessControl {
    /** @var array<string, array<string>> */
    private array $rules = [];
    
    /** @var bool */
    private bool $testMode = false;
    
    /**
     * Enable test mode
     * @internal This method should only be used in tests
     */
    public function enableTestMode(): void {
        $this->testMode = true;
    }
    
    /**
     * Disable test mode
     * @internal This method should only be used in tests
     */
    public function disableTestMode(): void {
        $this->testMode = false;
        $this->clearRules();
    }
    
    /**
     * Check if test mode is enabled
     */
    public function isTestMode(): bool {
        return $this->testMode;
    }
    
    /**
     * Add an access rule for a configuration key pattern
     *
     * @param string $pattern Pattern to match configuration keys (supports wildcards)
     * @param array<string> $permissions Array of permissions ('read', 'write')
     */
    public function addRule(string $pattern, array $permissions): void {
        $validPermissions = array_intersect($permissions, ['read', 'write']);
        if (empty($validPermissions)) {
            throw new \InvalidArgumentException('At least one valid permission (read/write) must be specified');
        }
        
        $this->rules[$pattern] = $validPermissions;
    }
    
    /**
     * Check if access is allowed for a key and permission
     *
     * @param string $key The configuration key to check
     * @param string $permission The required permission ('read' or 'write')
     * @return bool True if access is allowed, false otherwise
     */
    public function isAllowed(string $key, string $permission): bool {
        // Check exact match first
        if (isset($this->rules[$key])) {
            return in_array($permission, $this->rules[$key], true);
        }
        
        // Check pattern matches in order of specificity
        $bestMatch = null;
        $bestMatchLength = -1;
        
        foreach ($this->rules as $pattern => $permissions) {
            // Convert pattern to regex
            $regexPattern = str_replace(['*', '.'], ['[^.]*', '\\.'], $pattern);
            $regexPattern = '/^' . $regexPattern . '$/';
            
            if (preg_match($regexPattern, $key)) {
                $patternLength = strlen($pattern) - substr_count($pattern, '*');
                if ($patternLength > $bestMatchLength) {
                    $bestMatch = $permissions;
                    $bestMatchLength = $patternLength;
                }
            }
        }
        
        if ($bestMatch !== null) {
            return in_array($permission, $bestMatch, true);
        }
        
        // In test mode with no matching rules, allow access
        if ($this->testMode && empty($this->rules)) {
            return true;
        }
        
        // If no rule matches, default to denying access
        return false;
    }
    
    /**
     * Get all rules
     *
     * @return array<string, array<string>>
     */
    public function getRules(): array {
        return $this->rules;
    }
    
    /**
     * Clear all rules
     */
    public function clearRules(): void {
        $this->rules = [];
    }
}
