<?php
namespace Portfolion;

use RuntimeException;
use Defuse\Crypto\Key;
use Defuse\Crypto\Crypto;
use Portfolion\Config\ConfigEventDispatcher;
use Portfolion\Config\ConfigAccessControl;
use Portfolion\Config\Drivers\ConfigDriverInterface;
use Portfolion\Config\Drivers\FileDriver;

/**
 * Configuration management class that handles loading, caching, and validating configuration.
 * 
 * This class implements the Singleton pattern to ensure only one configuration instance exists
 * throughout the application lifecycle. It provides a centralized way to manage configuration
 * values from various sources including environment variables, PHP configuration files, and
 * runtime updates.
 * 
 * Features:
 * - Environment variable loading from .env files
 * - Configuration file loading from /config directory
 * - Configuration caching for production environments
 * - Dot notation access to nested configuration values
 * - Runtime configuration updates
 * - Configuration validation
 * 
 * Basic Usage:
 * ```php
 * $config = Config::getInstance();
 * 
 * // Get a configuration value
 * $dbHost = $config->get('database.host');
 * 
 * // Set a configuration value
 * $config->set('app.debug', true);
 * 
 * // Validate configuration
 * $config->validate('database', [
 *     'host' => ['type' => 'string', 'required' => true],
 *     'port' => ['type' => 'integer', 'required' => true]
 * ]);
 * ```
 * 
 * @package Portfolion
 */
class Config {
    /** @var self|null */
    private static ?self $instance = null;
    
    /** @var ConfigDriverInterface */
    private ConfigDriverInterface $driver;
    
    /** @var ConfigEventDispatcher */
    private ConfigEventDispatcher $events;
    
    /** @var ConfigAccessControl */
    private ConfigAccessControl $access;
    
    /** @var array<string, mixed> */
    protected array $config = [];
    
    /** @var array<string, array> */
    protected array $schemas = [];
    
    /** @var array<string, bool> */
    private array $loadedSections = [];
    
    /** @var string */
    private string $projectRoot;
    
    /** @var bool */
    private bool $isProduction;
    
    /** @var string */
    private string $cacheFile;
    
    /** @var string */
    private string $schemaCache;
    
    /** @var Key|null */
    private ?Key $encryptionKey = null;
    
    /** @var array<string> */
    private array $sensitiveKeys = [];

    private function __construct() {
        $this->isProduction = env('APP_ENV', 'local') === 'production';
        $this->projectRoot = dirname(dirname(__FILE__));
        $this->cacheFile = $this->projectRoot . '/storage/framework/cache/config.cache.php';
        $this->schemaCache = $this->projectRoot . '/storage/framework/cache/config.schema.php';
        
        $this->events = new ConfigEventDispatcher();
        $this->access = new ConfigAccessControl();
        $this->driver = new FileDriver($this->projectRoot . '/config');
        
        // Initialize encryption if key exists
        $keyPath = $this->projectRoot . '/storage/app/config.key';
        if (file_exists($keyPath)) {
            $this->encryptionKey = Key::loadFromAsciiSafeString(file_get_contents($keyPath));
        }
        
        // Load sensitive keys list
        $this->loadSensitiveKeys();
        
        // Set default access rules
        $this->access->addRule('*', ['read']);  // Everything is readable by default
        $this->access->addRule('app.*', ['read', 'write']);  // App config is fully accessible
        $this->access->addRule('database.*', ['read']);  // Ensure database config is readable
        $this->access->addRule('database.connections.*', ['read']);  // Ensure database connections are readable
        
        // Try loading from cache in production
        if ($this->isProduction && $this->loadFromCache()) {
            return;
        }
        
        // Load environment variables first
        $this->loadEnvironment();
        
        // Then load configuration files
        $this->loadConfigurations();
    }

    /**
     * Loads the list of sensitive configuration keys that should be encrypted
     */
    private function loadSensitiveKeys(): void {
        $sensitiveFile = $this->projectRoot . '/config/sensitive.php';
        if (file_exists($sensitiveFile)) {
            $keys = require $sensitiveFile;
            if (is_array($keys)) {
                $this->sensitiveKeys = $keys;
            }
        }
    }

    /**
     * Encrypts a configuration value if it's marked as sensitive
     */
    private function encryptValue(string $key, mixed $value): mixed {
        if (!$this->encryptionKey || !is_string($value)) {
            return $value;
        }

        foreach ($this->sensitiveKeys as $pattern) {
            if (fnmatch($pattern, $key)) {
                return Crypto::encrypt($value, $this->encryptionKey);
            }
        }

        return $value;
    }

    /**
     * Decrypts a configuration value if it's marked as sensitive
     */
    private function decryptValue(string $key, mixed $value): mixed {
        if (!$this->encryptionKey || !is_string($value)) {
            return $value;
        }

        foreach ($this->sensitiveKeys as $pattern) {
            if (fnmatch($pattern, $key)) {
                try {
                    return Crypto::decrypt($value, $this->encryptionKey);
                } catch (\Exception $e) {
                    // If decryption fails, assume the value wasn't encrypted
                    return $value;
                }
            }
        }

        return $value;
    }

    /**
     * Validates a configuration section against a cached schema.
     */
    public function validate(string $key, array $schema): void {
        // Cache the schema
        $this->schemas[$key] = $schema;
        if ($this->isProduction) {
            $this->saveSchemaCache();
        }
        
        $value = $this->get($key);
        
        foreach ($schema as $field => $rules) {
            if (!isset($value[$field]) && isset($rules['required']) && $rules['required']) {
                throw new RuntimeException("Missing required configuration field: {$key}.{$field}");
            }
            
            if (isset($value[$field]) && isset($rules['type'])) {
                $actualType = gettype($value[$field]);
                if ($actualType !== $rules['type']) {
                    throw new RuntimeException(
                        "Invalid type for configuration field {$key}.{$field}. " .
                        "Expected {$rules['type']}, got {$actualType}"
                    );
                }
            }
        }
    }

    /**
     * Save schemas to cache file
     */
    private function saveSchemaCache(): void {
        $schemas = $this->prepareForSerialization($this->schemas);
        file_put_contents(
            $this->schemaCache,
            '<?php return ' . var_export($schemas, true) . ';'
        );
    }

    /**
     * Load schemas from cache file
     */
    private function loadSchemaCache(): bool {
        if (!file_exists($this->schemaCache)) {
            return false;
        }

        $schemas = require $this->schemaCache;
        if (!is_array($schemas)) {
            return false;
        }

        $this->schemas = $schemas;
        return true;
    }

    /**
     * Loads configuration from cache file if it exists and is valid
     * 
     * @return bool True if cache was successfully loaded, false otherwise
     */
    private function loadFromCache(): bool {
        if (!file_exists($this->cacheFile)) {
            return false;
        }

        $cached = require $this->cacheFile;
        if (!is_array($cached) || !isset($cached['timestamp']) || !isset($cached['data'])) {
            return false;
        }

        // Cache expires after 1 hour in production
        if (time() - $cached['timestamp'] > 3600) {
            return false;
        }

        $this->config = $cached['data'];
        return true;
    }

    /**
     * Recursively processes an array to make it safe for serialization
     * by replacing Closure objects with placeholder strings
     * 
     * @param mixed $data The data to prepare
     * @return mixed The prepared data
     */
    private function prepareForSerialization($data) {
        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                $result[$key] = $this->prepareForSerialization($value);
            }
            return $result;
        } elseif ($data instanceof \Closure) {
            return '[Closure function]';
        } else {
            return $data;
        }
    }

    /**
     * Saves current configuration to cache file
     * 
     * @throws RuntimeException if cache directory cannot be created or file cannot be written
     */
    private function saveToCache(): void {
        $cacheDir = dirname($this->cacheFile);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $cache = [
            'timestamp' => time(),
            'data' => $this->prepareForSerialization($this->config)
        ];

        file_put_contents(
            $this->cacheFile,
            '<?php return ' . var_export($cache, true) . ';'
        );
    }

    /**
     * Gets a configuration value using dot notation with lazy loading
     */
    public function get(string $key, mixed $default = null): mixed {
        // Always allow access in test mode
        if (!$this->testMode && !$this->access->isAllowed($key, 'read')) {
            throw new RuntimeException("Access denied to configuration key: {$key}");
        }

        // For lazy loading sections
        $section = explode('.', $key)[0];
        if (!isset($this->loadedSections[$section])) {
            $this->loadSection($section);
        }

        $keys = explode('.', $key);
        $config = $this->config;
        
        foreach ($keys as $segment) {
            if (!is_array($config) || !array_key_exists($segment, $config)) {
                return $default;
            }
            $config = $config[$segment];
        }
        
        return $this->decryptValue($key, $config);
    }

    /**
     * Sets a configuration value using dot notation
     */
    public function set(string $key, mixed $value): void {
        // Skip access control in test mode
        if (!$this->testMode) {
            // For writes, we need to check access to the full path
            $keyParts = explode('.', $key);
            $currentPath = '';
            foreach ($keyParts as $part) {
                $currentPath .= ($currentPath === '' ? $part : '.' . $part);
                if (!$this->access->isAllowed($currentPath, 'write')) {
                    throw new RuntimeException("Access denied to configuration key: {$key}");
                }
            }
        }
        
        $oldValue = $this->get($key);
        $value = $this->encryptValue($key, $value);
        
        $keys = explode('.', $key);
        $config = &$this->config;
        
        foreach ($keys as $i => $segment) {
            if ($i === array_key_last($keys)) {
                $config[$segment] = $value;
                break;
            }
            
            if (!isset($config[$segment]) || !is_array($config[$segment])) {
                $config[$segment] = [];
            }
            
            $config = &$config[$segment];
        }
        
        // Dispatch value changed event
        $this->events->dispatch('config.value.changed', [
            'key' => $key,
            'old' => $oldValue,
            'new' => $value
        ]);
    }

    /**
     * Register an event listener
     */
    public function on(string $event, callable $listener): void {
        $this->events->addListener($event, $listener);
    }

    /**
     * Add an access control rule
     */
    public function addAccessRule(string $pattern, array $permissions): void {
        $this->access->addRule($pattern, $permissions);
    }

    /**
     * Set the configuration driver
     */
    public function setDriver(ConfigDriverInterface $driver): void {
        $this->driver = $driver;
        $this->loadedSections = [];
    }

    /** @var bool */
    private bool $testMode = false;
    
    /**
     * Enable full access for testing purposes
     * @internal This method should only be used in tests
     */
    public function enableTestMode(): void {
        $this->testMode = true;
        $this->access->enableTestMode();
    }

    /**
     * Temporarily disable test mode for access control testing
     * @internal This method should only be used in tests
     */
    public function disableTestMode(): void {
        $this->testMode = false;
        $this->access->disableTestMode();
        
        // Re-set default rules when disabling test mode
        $this->access->addRule('*', ['read']);  // Everything is readable by default
        $this->access->addRule('app.*', ['read', 'write']);  // App config is fully accessible
    }
    
    /**
     * Get the access control instance
     * @internal This method should only be used in tests
     * @return ConfigAccessControl
     */
    public function getAccessControl(): ConfigAccessControl {
        return $this->access;
    }
    
    /**
     * Check if test mode is enabled
     */
    public function isTestMode(): bool {
        return $this->testMode;
    }
    
    /**
     * Clears the configuration cache file if it exists.
     * 
     * This method should be called when configuration files are updated
     * to ensure the application uses the latest values.
     * 
     * @example
     * ```php
     * // After updating config files
     * $config->clearCache();
     * ```
     */
    public function clearCache(): void {
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }
    
    /**
     * Loads configuration values from environment file
     * 
     * @param string $path Path to the .env file
     * @throws RuntimeException if the file cannot be read
     */
    private function loadEnv(string $path): void {
        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException("Failed to read .env file: {$path}");
        }
        
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '' && strpos($line, '#') !== 0 && strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                $value = trim($value, '"\'');
                
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
            }
        }
    }
    
    private function loadEnvironment(): void {
        // First try to load composer's autoload which might already have env() function
        $autoloadPath = $this->projectRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
        }
        
        // Then ensure env.php is loaded
        $envPath = $this->projectRoot . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'env.php';
        error_log("Checking for env.php at: " . $envPath);
        
        if (!file_exists($envPath)) {
            throw new RuntimeException("Required env.php helper not found at: {$envPath}");
        }
         require_once $envPath;

        if (!function_exists('env')) {
            throw new RuntimeException("env() function not found after loading env.php helper");
        }
        
        // Load .env file
        $envPath = $this->projectRoot . DIRECTORY_SEPARATOR . '.env';
        if (file_exists($envPath)) {
            $this->loadEnv($envPath);
        }
    }
    
    private function loadConfigurations(): void {
        $configDir = $this->projectRoot . DIRECTORY_SEPARATOR . 'config';
        
        if (!is_dir($configDir)) {
            throw new RuntimeException("Config directory not found: {$configDir}");
        }
        
        // Use DirectoryIterator to handle spaces in paths
        $configFiles = [];
        foreach (new \DirectoryIterator($configDir) as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $configFiles[] = $file->getRealPath();
            }
        }
        
        if (empty($configFiles)) {
            throw new RuntimeException("No config files found in: {$configDir}");
        }
        
        foreach ($configFiles as $file) {
            $key = basename($file, '.php');
            $config = require $file;
            
            if (!is_array($config)) {
                throw new RuntimeException("Configuration file must return an array: {$file}");
            }
            
            $this->config[$key] = $config;
        }
    }
    
    /**
     * Load a configuration section
     */
    protected function loadSection(string $section): void {
        if ($this->testMode) {
            $this->loadedSections[$section] = true;
            return;
        }
        
        try {
            $data = $this->driver->load($section);
            if ($data !== null) {
                $this->config[$section] = $data;
            }
            $this->loadedSections[$section] = true;
        } catch (\Exception $e) {
            // Log the error but don't fail
            error_log("Failed to load config section {$section}: " . $e->getMessage());
            $this->loadedSections[$section] = false;
        }
    }
    
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __clone() {
        throw new RuntimeException('Config instances cannot be cloned');
    }
    
    public function __wakeup() {
        throw new RuntimeException('Config instances cannot be unserialized');
    }
}
