<?php
namespace Tests\Core;

use Tests\TestCase;
use Tests\Helpers\TestEnvironment;
use Portfolion\Container\Container;
use Portfolion\Config;
use Portfolion\View\TwigTemplate;
use Portfolion\Routing\Router;
use Portfolion\Cache\Cache;
use Portfolion\Queue\QueueManager;
use Portfolion\Events\EventDispatcher;
use Portfolion\Auth\AuthenticationService;
use Portfolion\Database\QueryBuilder;
use Portfolion\Database\Connection;

class CoreTest extends TestCase {
    private Container $container;
    private Config $config;
    private Connection $connection;
    
    protected function setUp(): void {
        // Set up testing environment
        TestEnvironment::setupTestConfig();
        TestEnvironment::setupDatabaseForTesting();
        
        $this->container = new Container();
        $this->config = Config::getInstance();
        $this->config->clearCache();
        $this->config->enableTestMode();
        
        // Set up database
        $this->connection = new Connection();
        TestEnvironment::createTestTables($this->connection);
        
        // Bind database connection to container
        $this->container->instance(Connection::class, $this->connection);
    }
    
    public function testContainerBindings() {
        $this->container->singleton(Config::class);
        $this->container->singleton(Router::class);
        $this->container->singleton(TwigTemplate::class);
        $this->container->singleton(Cache::class);
        $this->container->singleton(QueueManager::class);
        $this->container->singleton(EventDispatcher::class);
        
        $config = $this->container->make(Config::class);
        $router = $this->container->make(Router::class);
        $twig = $this->container->make(TwigTemplate::class);
        $cache = $this->container->make(Cache::class);
        $queue = $this->container->make(QueueManager::class);
        $events = $this->container->make(EventDispatcher::class);
        
        $this->assertInstanceOf(Config::class, $config);
        $this->assertInstanceOf(Router::class, $router);
        $this->assertInstanceOf(TwigTemplate::class, $twig);
        $this->assertInstanceOf(Cache::class, $cache);
        $this->assertInstanceOf(QueueManager::class, $queue);
        $this->assertInstanceOf(EventDispatcher::class, $events);
    }
    
    public function testEventDispatcher() {
        $dispatcher = $this->container->make(EventDispatcher::class);
        $triggered = false;
        
        $dispatcher->subscribe('test.event', function() use (&$triggered) {
            $triggered = true;
        });
        
        $dispatcher->dispatch('test.event', ['data' => 'test']);
        
        $this->assertTrue($triggered);
    }
    
    public function testCache() {
        $cache = $this->container->make(Cache::class);
        
        $cache->put('test_key', 'test_value', 60);
        $this->assertEquals('test_value', $cache->get('test_key'));
        
        $cache->forget('test_key');
        $this->assertNull($cache->get('test_key'));
    }
    
    public function testQueue() {
        $queue = $this->container->make(QueueManager::class);
        
        $job = ['test' => 'job'];
        $this->assertTrue($queue->push('default', $job));
        
        $popped = $queue->pop('default');
        $this->assertEquals($job, $popped['data']);
        
        $queue->delete($popped['id']);
    }
    
    public function testAuthentication() {
        $auth = $this->container->make(AuthenticationService::class);
        
        $this->assertFalse($auth->check());
        
        // Test login with the test user
        $result = $auth->attempt('test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
        $this->assertTrue($result);
        $this->assertTrue($auth->check());
        
        // Test logout
        $auth->logout();
        $this->assertFalse($auth->check());
    }
    
    public function testQueryBuilder() {
        $query = new QueryBuilder($this->connection);
        
        $users = $query->table('users')
            ->select(['id', 'name'])
            ->where('email', '=', 'test@example.com')
            ->get();
            
        $this->assertNotEmpty($users);
        $this->assertEquals('Test User', $users[0]['name']);
    }
}
