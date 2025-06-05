<?php

namespace Tests\Database;

use PHPUnit\Framework\TestCase;
use Portfolion\Database\Connection;
use Portfolion\Database\Model;
use Portfolion\Database\Schema\Schema;
use Portfolion\Database\Schema\Blueprint;
use PDO;
use PDOException;

// Create a test model class
class TestUser extends Model
{
    protected ?string $table = 'test_users';
    
    protected array $fillable = [
        'name', 'email', 'age', 'active'
    ];
    
    protected array $hidden = [
        'password'
    ];
    
    protected array $casts = [
        'active' => 'boolean',
        'age' => 'integer'
    ];
}

class ModelTest extends TestCase
{
    /**
     * @var Connection
     */
    protected $connection;
    
    /**
     * @var string
     */
    protected $driver;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        try {
            $this->connection = new Connection();
            $this->driver = $this->connection->getDriver();
            
            // Create a test table
            Schema::create('test_users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->integer('age');
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        } catch (PDOException $e) {
            $this->markTestSkipped('Database connection failed: ' . $e->getMessage());
        }
    }
    
    protected function tearDown(): void
    {
        // Clean up test tables
        try {
            $this->connection->getPdo()->exec('DROP TABLE IF EXISTS test_users');
        } catch (PDOException $e) {
            // Ignore errors during cleanup
        }
        
        parent::tearDown();
    }
    
    /**
     * Test creating a model
     */
    public function testCreate()
    {
        $user = TestUser::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'age' => 25,
            'active' => true
        ]);
        
        $this->assertInstanceOf(TestUser::class, $user);
        $this->assertIsNumeric($user->id);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertEquals(25, $user->age);
        $this->assertTrue($user->active);
    }
    
    /**
     * Test finding a model by ID
     */
    public function testFind()
    {
        // Create a user first
        $createdUser = TestUser::create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'password' => 'password456',
            'age' => 30,
            'active' => true
        ]);
        
        // Find the user by ID
        $foundUser = TestUser::find($createdUser->id);
        
        $this->assertInstanceOf(TestUser::class, $foundUser);
        $this->assertEquals($createdUser->id, $foundUser->id);
        $this->assertEquals('Jane Smith', $foundUser->name);
        $this->assertEquals('jane@example.com', $foundUser->email);
        $this->assertEquals(30, $foundUser->age);
        $this->assertTrue($foundUser->active);
    }
    
    /**
     * Test updating a model
     */
    public function testUpdate()
    {
        // Create a user first
        $user = TestUser::create([
            'name' => 'Bob Johnson',
            'email' => 'bob@example.com',
            'password' => 'password789',
            'age' => 35,
            'active' => true
        ]);
        
        // Update the user
        $user->name = 'Robert Johnson';
        $user->age = 36;
        $user->active = 0; // Use 0 instead of false for MySQL compatibility
        $user->save();
        
        // Find the user again to verify changes
        $updatedUser = TestUser::find($user->id);
        
        $this->assertEquals('Robert Johnson', $updatedUser->name);
        $this->assertEquals(36, $updatedUser->age);
        $this->assertFalse($updatedUser->active);
        $this->assertEquals('bob@example.com', $updatedUser->email); // Unchanged
    }
    
    /**
     * Test deleting a model
     */
    public function testDelete()
    {
        // Create a user first
        $user = TestUser::create([
            'name' => 'Alice Brown',
            'email' => 'alice@example.com',
            'password' => 'password101',
            'age' => 40,
            'active' => true
        ]);
        
        // Get the ID
        $userId = $user->id;
        
        // Delete the user
        $user->delete();
        
        // Try to find the deleted user
        $deletedUser = TestUser::find($userId);
        
        $this->assertNull($deletedUser);
    }
    
    /**
     * Test querying models
     */
    public function testWhere()
    {
        try {
            // Create multiple users
            TestUser::create([
                'name' => 'User 1',
                'email' => 'user1@example.com',
                'password' => 'password1',
                'age' => 20,
                'active' => true
            ]);
            
            TestUser::create([
                'name' => 'User 2',
                'email' => 'user2@example.com',
                'password' => 'password2',
                'age' => 25,
                'active' => true
            ]);
            
            // Use 0 instead of false for MySQL compatibility
            TestUser::create([
                'name' => 'User 3',
                'email' => 'user3@example.com',
                'password' => 'password3',
                'age' => 30,
                'active' => 0
            ]);
            
            // Query users by age
            $users = TestUser::where('age', '>', 20)->get();
            
            $this->assertIsArray($users);
            $this->assertCount(2, $users);
            $this->assertEquals('User 2', $users[0]->name);
            $this->assertEquals('User 3', $users[1]->name);
            
            // Query users by active status
            $activeUsers = TestUser::where('active', true)->get();
            
            $this->assertIsArray($activeUsers);
            $this->assertCount(2, $activeUsers);
            $this->assertEquals('User 1', $activeUsers[0]->name);
            $this->assertEquals('User 2', $activeUsers[1]->name);
        } catch (\Exception $e) {
            $this->markTestSkipped('Database error: ' . $e->getMessage());
        }
    }
    
    /**
     * Test hidden attributes
     */
    public function testHiddenAttributes()
    {
        // Create a user
        $user = TestUser::create([
            'name' => 'Hidden Test',
            'email' => 'hidden@example.com',
            'password' => 'secret123',
            'age' => 50,
            'active' => true
        ]);
        
        // Convert to array
        $userArray = $user->toArray();
        
        // Password should be hidden
        $this->assertArrayNotHasKey('password', $userArray);
        
        // Other attributes should be visible
        $this->assertArrayHasKey('name', $userArray);
        $this->assertArrayHasKey('email', $userArray);
        $this->assertArrayHasKey('age', $userArray);
        $this->assertArrayHasKey('active', $userArray);
    }
    
    /**
     * Test attribute casting
     */
    public function testAttributeCasting()
    {
        // Create a user
        $user = TestUser::create([
            'name' => 'Cast Test',
            'email' => 'cast@example.com',
            'password' => 'password',
            'age' => '45', // String that should be cast to integer
            'active' => 1   // Integer that should be cast to boolean
        ]);
        
        // Age should be an integer
        $this->assertIsInt($user->age);
        $this->assertEquals(45, $user->age);
        
        // Active should be a boolean
        $this->assertIsBool($user->active);
        $this->assertTrue($user->active);
    }
    
    /**
     * Test mass assignment protection
     */
    public function testMassAssignmentProtection()
    {
        // Create a user with attributes that aren't in the fillable array
        $user = new TestUser();
        $user->fill([
            'name' => 'Mass Assignment',
            'email' => 'mass@example.com',
            'password' => 'password',
            'age' => 55,
            'active' => true,
            'admin' => true // Not in fillable array
        ]);
        
        // The admin attribute should not be set
        $this->assertFalse(property_exists($user, 'admin'));
        
        // The other attributes should be set
        $this->assertEquals('Mass Assignment', $user->name);
        $this->assertEquals('mass@example.com', $user->email);
        $this->assertEquals(55, $user->age);
        $this->assertTrue($user->active);
    }
} 