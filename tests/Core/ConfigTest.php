<?php

namespace Tests\Core;

use PHPUnit\Framework\TestCase;
use Portfolion\Config;
use Portfolion\Config\Drivers\FileDriver;
use Portfolion\Config\Drivers\RedisDriver;
use Portfolion\Config\Drivers\DatabaseDriver;
use RuntimeException;

class ConfigTest extends TestCase {
    private static Config $config;
    private static string $testEnvFile;
    private static string $testConfigFile;
    private static string $testKeyFile;

    public static function setUpBeforeClass(): void {
        // Create test environment file with testing environment
        self::$testEnvFile = sys_get_temp_dir() . '/.env.testing';
        putenv('APP_ENV=testing');
        file_put_contents(self::$testEnvFile, "APP_ENV=testing\nDB_HOST=localhost\n");

        // Create test config file
        self::$testConfigFile = sys_get_temp_dir() . '/test.php';
        file_put_contents(
            self::$testConfigFile,
            '<?php return ["database" => ["host" => "localhost", "port" => 3306]];'
        );

        // Create test encryption key
        self::$testKeyFile = sys_get_temp_dir() . '/config.key';
        if (!file_exists(self::$testKeyFile)) {
            $key = \Defuse\Crypto\Key::createNewRandomKey();
            file_put_contents(self::$testKeyFile, $key->saveToAsciiSafeString());
        }
    }

    public static function tearDownAfterClass(): void {
        @unlink(self::$testEnvFile);
        @unlink(self::$testConfigFile);
        @unlink(self::$testKeyFile);
    }

    protected function setUp(): void {
        // Clear any cached instance
        $reflectionClass = new \ReflectionClass(Config::class);
        $instanceProperty = $reflectionClass->getProperty('instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null, null);

        // Get fresh instance and enable test mode
        $config = Config::getInstance();
        $config->clearCache();
        
        // Clear any existing rules
        $config->getAccessControl()->clearRules();
        
        // Start in test mode by default
        $config->enableTestMode();
    }

    public function testSingleton(): void {
        $instance1 = Config::getInstance();
        $instance2 = Config::getInstance();
        
        $this->assertSame($instance1, $instance2);
    }

    public function testGetAndSet(): void {
        $config = Config::getInstance();
        
        $config->set('test.key', 'value');
        $this->assertEquals('value', $config->get('test.key'));
        
        $config->set('test.nested.key', 'nested-value');
        $this->assertEquals('nested-value', $config->get('test.nested.key'));
    }

    public function testValidation(): void {
        $config = Config::getInstance();
        
        $config->set('database', [
            'host' => 'localhost',
            'port' => 3306
        ]);

        $schema = [
            'host' => ['type' => 'string', 'required' => true],
            'port' => ['type' => 'integer', 'required' => true]
        ];

        // Should not throw
        $config->validate('database', $schema);

        $this->expectException(RuntimeException::class);
        $config->validate('database', [
            'missing' => ['type' => 'string', 'required' => true]
        ]);
    }

    public function testLazyLoading(): void {
        $config = Config::getInstance();
        
        // Set test data
        $config->set('lazy_section.key', 'value');
        
        // Clear internal state
        $reflectionClass = new \ReflectionClass(Config::class);
        $loadedSections = $reflectionClass->getProperty('loadedSections');
        $loadedSections->setAccessible(true);
        $loadedSections->setValue($config, []);
        
        // Access should trigger lazy loading
        $this->assertEquals('value', $config->get('lazy_section.key'));
    }

    public function testConfigurationEvents(): void {
        $config = Config::getInstance();
        $eventFired = false;
        
        $config->on('config.value.changed', function($payload) use (&$eventFired) {
            $eventFired = true;
        });
        
        $config->set('test.event', 'value');
        $this->assertTrue($eventFired);
    }

    public function testAccessControl(): void {
        $config = Config::getInstance();
        
        // Important: disable test mode first to properly test access control
        $config->disableTestMode();
        
        // Then set up access rules for testing
        $config->addAccessRule('*', ['read']);  // Everything is readable
        $config->addAccessRule('app.*', ['read', 'write']);  // Full access to app section
        $config->addAccessRule('secure.*', ['read']);  // Read-only secure section
        
        try {
            // First verify app section (should allow both read and write)
            $config->set('app.key', 'value');
            $this->assertEquals('value', $config->get('app.key'));
            
            // Then verify read access to secure section (should work)
            $this->assertEquals(null, $config->get('secure.key'));
            
            // Finally verify write access to secure section (should fail)
            try {
                $config->set('secure.key', 'new-value');
                $this->fail('Expected RuntimeException was not thrown');
            } catch (RuntimeException $e) {
                $this->assertEquals('Access denied to configuration key: secure.key', $e->getMessage());
            }
            
        } finally {
            // Always restore test mode after the test
            $config->enableTestMode();
        }
    }

    public function testCaching(): void {
        $config = Config::getInstance();
        
        // Set test data
        $config->set('test.cache', 'cached-value');
        
        // Force production mode and save cache
        $reflectionClass = new \ReflectionClass(Config::class);
        $isProductionProperty = $reflectionClass->getProperty('isProduction');
        $isProductionProperty->setAccessible(true);
        $isProductionProperty->setValue($config, true);
        
        // Save to cache
        $saveToCache = $reflectionClass->getMethod('saveToCache');
        $saveToCache->setAccessible(true);
        $saveToCache->invoke($config);
        
        // Clear current config
        $configProperty = $reflectionClass->getProperty('config');
        $configProperty->setAccessible(true);
        $configProperty->setValue($config, []);
        
        // Load from cache
        $loadFromCache = $reflectionClass->getMethod('loadFromCache');
        $loadFromCache->setAccessible(true);
        $result = $loadFromCache->invoke($config);
        
        $this->assertTrue($result);
        $this->assertEquals('cached-value', $config->get('test.cache'));
    }
    
    public function testSchemaCache(): void {
        $config = Config::getInstance();
        
        $schema = [
            'host' => ['type' => 'string', 'required' => true],
            'port' => ['type' => 'integer', 'required' => true]
        ];
        
        // Set test data and validate
        $config->set('database', [
            'host' => 'localhost',
            'port' => 3306
        ]);
        
        $config->validate('database', $schema);
        
        // Force save schema cache
        $reflectionClass = new \ReflectionClass(Config::class);
        $saveSchemaCache = $reflectionClass->getMethod('saveSchemaCache');
        $saveSchemaCache->setAccessible(true);
        $saveSchemaCache->invoke($config);
        
        // Clear schemas
        $schemasProperty = $reflectionClass->getProperty('schemas');
        $schemasProperty->setAccessible(true);
        $schemasProperty->setValue($config, []);
        
        // Load from schema cache
        $loadSchemaCache = $reflectionClass->getMethod('loadSchemaCache');
        $loadSchemaCache->setAccessible(true);
        $result = $loadSchemaCache->invoke($config);
        
        $this->assertTrue($result);
    }

    public function testRedisDriver(): void {
        // Create a mock Redis object
        $redisMock = $this->createMock(\Redis::class);
        
        // Configure the mock to return values
        $redisMock->method('hGet')
            ->willReturnCallback(function($key, $field) {
                static $data = [];
                return $data[$key][$field] ?? null;
            });
            
        $redisMock->method('hSet')
            ->willReturnCallback(function($key, $field, $value) {
                static $data = [];
                $data[$key][$field] = $value;
                return true;
            });
            
        $redisMock->method('hGetAll')
            ->willReturnCallback(function($key) {
                static $data = [];
                return $data[$key] ?? [];
            });
            
        $redisMock->method('exists')
            ->willReturn(true);
            
        $redisMock->method('del')
            ->willReturn(true);
            
        $config = Config::getInstance();
        
        $driver = new RedisDriver($redisMock);
        $config->setDriver($driver);
        
        // Test setting and getting values
        $config->set('redis_test.key', 'value');
        $this->assertEquals('value', $config->get('redis_test.key'));
        
        // Test nested values
        $config->set('redis_test.nested.key', 'nested_value');
        $this->assertEquals('nested_value', $config->get('redis_test.nested.key'));
    }

    public function testDatabaseDriver(): void {
        $config = Config::getInstance();
        
        try {
            // Set up database connection using SQLite in-memory
            putenv('DB_CONNECTION=sqlite');
            putenv('DB_DATABASE=:memory:');
            
            $connection = new \Portfolion\Database\Connection();
            
            // Create configs table
            $connection->execute('
                CREATE TABLE IF NOT EXISTS configs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    section VARCHAR(255) NOT NULL,
                    key VARCHAR(255) NOT NULL,
                    value TEXT,
                    created_at TIMESTAMP,
                    updated_at TIMESTAMP,
                    UNIQUE(section, key)
                )
            ');
            
            $driver = new DatabaseDriver($connection);
            $config->setDriver($driver);
            
            // Test setting and getting values
            $config->set('db_test.key', 'value');
            $this->assertEquals('value', $config->get('db_test.key'));
            
            // Test nested values
            $config->set('db_test.nested.key', 'nested_value');
            $this->assertEquals('nested_value', $config->get('db_test.nested.key'));
            
            // Test retrieving all values in a section
            $section = $config->getSection('db_test');
            $this->assertIsArray($section);
            $this->assertEquals('value', $section['key']);
            $this->assertIsArray($section['nested']);
            $this->assertEquals('nested_value', $section['nested']['key']);
            
        } catch (\PDOException $e) {
            $this->fail('Database driver test failed: ' . $e->getMessage());
        }
    }
}
