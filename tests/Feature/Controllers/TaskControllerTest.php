<?php

namespace Tests\Feature\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\TaskController;
use App\Models\Task;
use Portfolion\Http\Request;
use Portfolion\Http\Response;
use Portfolion\Database\DB;

class TaskControllerTest extends TestCase
{
    protected TaskController $controller;
    protected Request $request;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create and set up the test database
        $this->setUpDatabase();
        
        $this->controller = new TaskController();
        $this->request = new Request();
    }
    
    /**
     * Set up a test database with required tables
     */
    protected function setUpDatabase(): void
    {
        // Get database connection
        $pdo = DB::connection()->getPdo();
        $driver = DB::connection()->getDriver();
        
        // Create tasks table if it doesn't exist
        $pdo->exec("DROP TABLE IF EXISTS tasks");
        
        // Use appropriate SQL syntax based on the database driver
        if ($driver === 'sqlite') {
            $pdo->exec("
                CREATE TABLE tasks (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    status VARCHAR(50) DEFAULT 'pending',
                    priority INTEGER DEFAULT 1,
                    due_date DATE,
                    user_id INTEGER NULL,
                    created_at DATETIME,
                    updated_at DATETIME
                )
            ");
            
            // Insert test data with SQLite datetime function
            $pdo->exec("
                INSERT INTO tasks (title, description, status, priority, due_date, user_id, created_at, updated_at)
                VALUES 
                ('Test Task 1', 'Description for task 1', 'pending', 1, '2023-12-31', NULL, datetime('now'), datetime('now')),
                ('Test Task 2', 'Description for task 2', 'completed', 2, '2023-12-25', NULL, datetime('now'), datetime('now'))
            ");
        } else {
            // MySQL and other databases
            $pdo->exec("
                CREATE TABLE tasks (
                    id INTEGER PRIMARY KEY AUTO_INCREMENT,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    status VARCHAR(50) DEFAULT 'pending',
                    priority INTEGER DEFAULT 1,
                    due_date DATE,
                    user_id INTEGER NULL,
                    created_at DATETIME,
                    updated_at DATETIME
                )
            ");
            
            // Insert test data with MySQL NOW() function
            $pdo->exec("
                INSERT INTO tasks (title, description, status, priority, due_date, user_id, created_at, updated_at)
                VALUES 
                ('Test Task 1', 'Description for task 1', 'pending', 1, '2023-12-31', NULL, NOW(), NOW()),
                ('Test Task 2', 'Description for task 2', 'completed', 2, '2023-12-25', NULL, NOW(), NOW())
            ");
        }
    }
    
    public function testIndex()
    {
        $response = $this->controller->index($this->request);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
    
    public function testCreate()
    {
        $response = $this->controller->create($this->request);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
    
    public function testShow()
    {
        $response = $this->controller->show($this->request, 1);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
    
    public function testEdit()
    {
        $response = $this->controller->edit($this->request, 1);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
    
    public function testStore()
    {
        // Set up the request data
        $postData = [
            'title' => 'New Test Task',
            'description' => 'This is a test task',
            'status' => 'pending',
            'priority' => 1,
            'due_date' => '2024-12-31'
        ];
        
        // Force POST method for the test
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        $request = new Request($postData);
        
        // Debug the request data
        echo "Store request data: " . print_r($request->all(), true) . "\n";
        
        $response = $this->controller->store($request);
        
        $this->assertInstanceOf(Response::class, $response);
        
        // Verify the task was created
        $pdo = DB::connection()->getPdo();
        $stmt = $pdo->query("SELECT * FROM tasks WHERE title = 'New Test Task'");
        $task = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertNotFalse($task);
        $this->assertEquals('New Test Task', $task['title']);
        $this->assertEquals('pending', $task['status']);
        $this->assertEquals(1, $task['priority']);
    }
    
    public function testUpdate()
    {
        // Set up the request data
        $postData = [
            'title' => 'Updated Task',
            'description' => 'This task has been updated',
            'status' => 'completed',
            'priority' => 2,
            'due_date' => '2024-06-30'
        ];
        
        // Force POST method for the test
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        $request = new Request($postData);
        
        // Debug the request data
        echo "Update request data: " . print_r($request->all(), true) . "\n";
        
        $response = $this->controller->update($request, 1);
        
        $this->assertInstanceOf(Response::class, $response);
        
        // Verify the task was updated
        $pdo = DB::connection()->getPdo();
        $stmt = $pdo->query("SELECT * FROM tasks WHERE id = 1");
        $task = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertNotFalse($task);
        $this->assertEquals('Updated Task', $task['title']);
        $this->assertEquals('completed', $task['status']);
        $this->assertEquals(2, $task['priority']);
    }
    
    public function testDestroy()
    {
        $response = $this->controller->destroy($this->request, 2);
        
        $this->assertInstanceOf(Response::class, $response);
        
        // Verify the task was deleted
        $pdo = DB::connection()->getPdo();
        $stmt = $pdo->query("SELECT COUNT(*) FROM tasks WHERE id = 2");
        $count = $stmt->fetchColumn();
        
        $this->assertEquals(0, $count);
    }
} 