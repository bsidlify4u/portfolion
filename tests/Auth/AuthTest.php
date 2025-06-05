<?php

namespace Tests\Auth;

use Tests\TestCase;
use Portfolion\Auth\Auth;
use Portfolion\Auth\User;
use Portfolion\Database\Connection;
use Portfolion\Database\Schema\Schema;
use Portfolion\Database\Schema\Blueprint;
use Portfolion\Session\Session;
use PDOException;

class AuthTest extends TestCase
{
    /**
     * @var Auth
     */
    protected $auth;
    
    /**
     * @var Connection
     */
    protected $connection;
    
    /**
     * @var Session
     */
    protected $session;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        try {
            // Create a database connection
            $this->connection = new Connection();
            
            // Create a session mock
            $this->session = $this->mock(Session::class, [
                'get' => null,
                'put' => true,
                'forget' => true
            ]);
            
            // Create the auth instance
            $this->auth = new Auth($this->connection, $this->session);
            
            // Create the users table
            $this->createUsersTable();
            
            // Create test users
            $this->createTestUsers();
        } catch (PDOException $e) {
            $this->markTestSkipped('Database connection failed: ' . $e->getMessage());
        }
    }
    
    protected function tearDown(): void
    {
        // Clean up the users table
        if ($this->connection) {
            try {
                $this->connection->getPdo()->exec('DROP TABLE IF EXISTS users');
            } catch (PDOException $e) {
                // Ignore errors during cleanup
            }
        }
        
        parent::tearDown();
    }
    
    /**
     * Create the users table for testing
     */
    protected function createUsersTable(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('remember_token')->nullable();
            $table->timestamps();
        });
    }
    
    /**
     * Create test users
     */
    protected function createTestUsers(): void
    {
        // Hash a password
        $hashedPassword = password_hash('password', PASSWORD_DEFAULT);
        
        // Create a test user
        $this->connection->execute('
            INSERT INTO users (name, email, password, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?)
        ', [
            'Test User',
            'test@example.com',
            $hashedPassword,
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s')
        ]);
    }
    
    public function testLoginWithValidCredentials()
    {
        // Attempt to login with valid credentials
        $result = $this->auth->attempt([
            'email' => 'test@example.com',
            'password' => 'password'
        ]);
        
        // Verify the login was successful
        $this->assertTrue($result);
        
        // Verify the user is authenticated
        $this->assertTrue($this->auth->check());
        
        // Verify the authenticated user
        $user = $this->auth->user();
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
    }
    
    public function testLoginWithInvalidCredentials()
    {
        // Attempt to login with invalid credentials
        $result = $this->auth->attempt([
            'email' => 'test@example.com',
            'password' => 'wrong-password'
        ]);
        
        // Verify the login failed
        $this->assertFalse($result);
        
        // Verify the user is not authenticated
        $this->assertFalse($this->auth->check());
        
        // Verify there is no authenticated user
        $this->assertNull($this->auth->user());
    }
    
    public function testLoginWithNonExistentUser()
    {
        // Attempt to login with non-existent user
        $result = $this->auth->attempt([
            'email' => 'nonexistent@example.com',
            'password' => 'password'
        ]);
        
        // Verify the login failed
        $this->assertFalse($result);
        
        // Verify the user is not authenticated
        $this->assertFalse($this->auth->check());
    }
    
    public function testLogout()
    {
        // Login first
        $this->auth->attempt([
            'email' => 'test@example.com',
            'password' => 'password'
        ]);
        
        // Verify the user is authenticated
        $this->assertTrue($this->auth->check());
        
        // Logout
        $this->auth->logout();
        
        // Verify the user is no longer authenticated
        $this->assertFalse($this->auth->check());
        $this->assertNull($this->auth->user());
    }
    
    public function testLoginById()
    {
        // Get the user ID
        $userId = $this->connection->select('SELECT id FROM users WHERE email = ?', ['test@example.com'])[0]['id'];
        
        // Login by ID
        $result = $this->auth->loginById($userId);
        
        // Verify the login was successful
        $this->assertTrue($result);
        
        // Verify the user is authenticated
        $this->assertTrue($this->auth->check());
        
        // Verify the authenticated user
        $user = $this->auth->user();
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($userId, $user->id);
    }
    
    public function testLoginWithRemember()
    {
        // Attempt to login with remember
        $result = $this->auth->attempt([
            'email' => 'test@example.com',
            'password' => 'password'
        ], true);
        
        // Verify the login was successful
        $this->assertTrue($result);
        
        // Verify the user is authenticated
        $this->assertTrue($this->auth->check());
        
        // Verify a remember token was generated
        $user = $this->auth->user();
        $this->assertNotNull($user->remember_token);
    }
    
    public function testRegisterUser()
    {
        // Register a new user
        $user = $this->auth->register([
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password'
        ]);
        
        // Verify the user was created
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('New User', $user->name);
        $this->assertEquals('new@example.com', $user->email);
        
        // Verify the password was hashed
        $this->assertTrue(password_verify('password', $user->password));
        
        // Verify the user exists in the database
        $dbUser = $this->connection->select('SELECT * FROM users WHERE email = ?', ['new@example.com']);
        $this->assertCount(1, $dbUser);
        $this->assertEquals('New User', $dbUser[0]['name']);
    }
} 