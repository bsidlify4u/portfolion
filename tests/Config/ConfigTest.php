<?php

namespace Tests\Config;

use Tests\TestCase;
use Portfolion\Config;

class ConfigTest extends TestCase
{
    /**
     * @var Config
     */
    protected $config;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->config = new Config();
    }
    
    public function testLoadConfigFile()
    {
        // Create a temporary config file
        $tempFile = sys_get_temp_dir() . '/test_config.php';
        file_put_contents($tempFile, '<?php return ["test" => "value"];');
        
        // Load the config file
        $this->config->load($tempFile);
        
        // Verify the config was loaded
        $this->assertEquals('value', $this->config->get('test'));
        
        // Clean up
        unlink($tempFile);
    }
    
    public function testGetConfigValue()
    {
        // Set a value
        $this->config->set('app.name', 'Portfolion');
        
        // Get the value
        $value = $this->config->get('app.name');
        
        // Verify the value
        $this->assertEquals('Portfolion', $value);
    }
    
    public function testGetConfigValueWithDefault()
    {
        // Get a non-existent value with a default
        $value = $this->config->get('non.existent', 'default');
        
        // Verify the default was returned
        $this->assertEquals('default', $value);
    }
    
    public function testSetConfigValue()
    {
        // Set a simple value
        $this->config->set('simple', 'value');
        
        // Verify the value was set
        $this->assertEquals('value', $this->config->get('simple'));
        
        // Set a nested value
        $this->config->set('nested.key', 'nested-value');
        
        // Verify the nested value was set
        $this->assertEquals('nested-value', $this->config->get('nested.key'));
    }
    
    public function testHasConfigValue()
    {
        // Set a value
        $this->config->set('exists', 'value');
        
        // Check if the value exists
        $this->assertTrue($this->config->has('exists'));
        
        // Check if a non-existent value exists
        $this->assertFalse($this->config->has('does.not.exist'));
    }
    
    public function testGetAllConfig()
    {
        // Set some values
        $this->config->set('app.name', 'Portfolion');
        $this->config->set('app.version', '1.0.0');
        
        // Get all config values
        $all = $this->config->all();
        
        // Verify the values
        $this->assertIsArray($all);
        $this->assertArrayHasKey('app', $all);
        $this->assertEquals('Portfolion', $all['app']['name']);
        $this->assertEquals('1.0.0', $all['app']['version']);
    }
    
    public function testEnvironmentSpecificConfig()
    {
        // Set the environment
        $_ENV['APP_ENV'] = 'testing';
        
        // Create temporary config files
        $baseFile = sys_get_temp_dir() . '/config.php';
        $envFile = sys_get_temp_dir() . '/config.testing.php';
        
        file_put_contents($baseFile, '<?php return ["setting" => "base"];');
        file_put_contents($envFile, '<?php return ["setting" => "testing"];');
        
        // Load the config files
        $this->config->load($baseFile);
        
        // Verify the environment-specific value was used
        $this->assertEquals('testing', $this->config->get('setting'));
        
        // Clean up
        unlink($baseFile);
        unlink($envFile);
    }
} 