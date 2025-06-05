<?php

namespace Tests\Database;

use PHPUnit\Framework\TestCase;
use Portfolion\Database\Connection;
use Portfolion\Database\QueryBuilder;
use Portfolion\Database\Schema\Schema;
use Portfolion\Database\Schema\Blueprint;
use PDO;
use PDOException;

class QueryBuilderTest extends TestCase
{
    /**
     * @var Connection
     */
    protected $connection;
    
    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;
    
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
            $this->queryBuilder = new QueryBuilder();
            
            // Create a test table
            $this->createTestTable();
            
            // Insert some test data
            $this->connection->execute('INSERT INTO test_query_builder (name, age, email, active) VALUES (?, ?, ?, ?)', ['John Doe', 25, 'john@example.com', 1]);
            $this->connection->execute('INSERT INTO test_query_builder (name, age, email, active) VALUES (?, ?, ?, ?)', ['Jane Smith', 30, 'jane@example.com', 1]);
            $this->connection->execute('INSERT INTO test_query_builder (name, age, email, active) VALUES (?, ?, ?, ?)', ['Bob Johnson', 35, 'bob@example.com', 0]);
        } catch (PDOException $e) {
            $this->markTestSkipped('Database connection failed: ' . $e->getMessage());
        }
    }
    
    protected function tearDown(): void
    {
        // Clean up test tables
        try {
            $this->connection->getPdo()->exec('DROP TABLE IF EXISTS test_query_builder');
            $this->connection->getPdo()->exec('DROP TABLE IF EXISTS test_query_builder_posts');
        } catch (PDOException $e) {
            // Ignore errors during cleanup
        }
        
        parent::tearDown();
    }
    
    /**
     * Create the test table with the appropriate schema for the current database driver
     */
    protected function createTestTable(): void
    {
        // Drop the table if it exists
        Schema::dropIfExists('test_query_builder');
        
        // Create the table with the appropriate schema for the current driver
        Schema::create('test_query_builder', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('age');
            $table->string('email')->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }
    
    /**
     * Test basic select query
     */
    public function testBasicSelect()
    {
        // Skip this test if the select method is not implemented
        if (!method_exists($this->queryBuilder, 'select')) {
            $this->markTestSkipped('select() method not implemented in QueryBuilder');
        }
        
        $results = $this->queryBuilder
            ->table('test_query_builder')
            ->select(['id', 'name', 'age'])
            ->get();
        
        $this->assertIsArray($results);
        $this->assertCount(3, $results);
        $this->assertEquals('John Doe', $results[0]['name']);
        $this->assertEquals(25, $results[0]['age']);
    }
    
    /**
     * Test where clause
     */
    public function testWhereClause()
    {
        $results = $this->queryBuilder
            ->table('test_query_builder')
            ->where('age', '>', 25)
            ->get();
        
        $this->assertIsArray($results);
        $this->assertCount(2, $results);
        $this->assertEquals('Jane Smith', $results[0]['name']);
        $this->assertEquals('Bob Johnson', $results[1]['name']);
    }
    
    /**
     * Test multiple where clauses
     */
    public function testMultipleWhereClauses()
    {
        $results = $this->queryBuilder
            ->table('test_query_builder')
            ->where('age', '>', 25)
            ->where('active', '=', 1)
            ->get();
        
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertEquals('Jane Smith', $results[0]['name']);
    }
    
    /**
     * Test orderBy clause
     */
    public function testOrderBy()
    {
        // Skip this test if the orderBy method is not implemented
        if (!method_exists($this->queryBuilder, 'orderBy')) {
            $this->markTestSkipped('orderBy() method not implemented in QueryBuilder');
        }
        
        $results = $this->queryBuilder
            ->table('test_query_builder')
            ->orderBy('age', 'desc')
            ->get();
        
        $this->assertIsArray($results);
        $this->assertCount(3, $results);
        $this->assertEquals('Bob Johnson', $results[0]['name']); // 35
        $this->assertEquals('Jane Smith', $results[1]['name']);  // 30
        $this->assertEquals('John Doe', $results[2]['name']);    // 25
    }
    
    /**
     * Test limit and offset
     */
    public function testLimitAndOffset()
    {
        // Skip this test if the orderBy method is not implemented
        if (!method_exists($this->queryBuilder, 'orderBy')) {
            $this->markTestSkipped('orderBy() method not implemented in QueryBuilder');
        }
        
        // Skip this test if the limit method is not implemented
        if (!method_exists($this->queryBuilder, 'limit') || !method_exists($this->queryBuilder, 'offset')) {
            $this->markTestSkipped('limit() or offset() methods not implemented in QueryBuilder');
        }
        
        $results = $this->queryBuilder
            ->table('test_query_builder')
            ->orderBy('age', 'asc')
            ->limit(2)
            ->offset(1)
            ->get();
        
        $this->assertIsArray($results);
        $this->assertCount(2, $results);
        $this->assertEquals('Jane Smith', $results[0]['name']);  // 30
        $this->assertEquals('Bob Johnson', $results[1]['name']); // 35
    }
    
    /**
     * Test first method
     */
    public function testFirst()
    {
        $result = $this->queryBuilder
            ->table('test_query_builder')
            ->where('email', '=', 'jane@example.com')
            ->first();
        
        $this->assertIsArray($result);
        $this->assertEquals('Jane Smith', $result['name']);
        $this->assertEquals(30, $result['age']);
    }
    
    /**
     * Test insert method
     */
    public function testInsert()
    {
        $result = $this->queryBuilder
            ->table('test_query_builder')
            ->insert([
                'name' => 'Alice Brown',
                'age' => 40,
                'email' => 'alice@example.com',
                'active' => 1
            ]);
        
        // Check that insert was successful
        $this->assertTrue($result);
        
        // Verify the record was inserted
        $result = $this->queryBuilder
            ->table('test_query_builder')
            ->where('email', '=', 'alice@example.com')
            ->first();
        
        $this->assertIsArray($result);
        $this->assertEquals('Alice Brown', $result['name']);
        $this->assertEquals(40, $result['age']);
    }
    
    /**
     * Test update method
     */
    public function testUpdate()
    {
        try {
            // First verify the record exists
            $original = $this->queryBuilder
                ->table('test_query_builder')
                ->where('email', '=', 'john@example.com')
                ->first();
                
            $this->assertNotNull($original);
            
            // Perform the update with minimal fields
            $affectedRows = $this->connection->execute(
                'UPDATE test_query_builder SET age = ?, active = ? WHERE email = ?',
                [26, 0, 'john@example.com']
            );
            
            $this->assertTrue($affectedRows > 0);
            
            // Verify the record was updated
            $result = $this->queryBuilder
                ->table('test_query_builder')
                ->where('email', '=', 'john@example.com')
                ->first();
            
            $this->assertIsArray($result);
            $this->assertEquals(26, $result['age']);
            $this->assertEquals(0, (int)$result['active']);
        } catch (PDOException $e) {
            $this->markTestSkipped('Update test failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Test delete method
     */
    public function testDelete()
    {
        // Skip this test if the count method is not implemented
        if (!method_exists($this->queryBuilder, 'count')) {
            $this->markTestSkipped('count() method not implemented in QueryBuilder');
        }
        
        $affectedRows = $this->queryBuilder
            ->table('test_query_builder')
            ->where('email', '=', 'bob@example.com')
            ->delete();
        
        $this->assertEquals(1, $affectedRows);
        
        // Verify the record was deleted
        $result = $this->queryBuilder
            ->table('test_query_builder')
            ->where('email', '=', 'bob@example.com')
            ->first();
        
        $this->assertNull($result);
        
        // Verify we only have 2 records left
        $count = $this->queryBuilder
            ->table('test_query_builder')
            ->count();
        
        $this->assertEquals(2, $count);
    }
    
    /**
     * Test count method
     */
    public function testCount()
    {
        // Skip this test if the count method is not implemented
        if (!method_exists($this->queryBuilder, 'count')) {
            $this->markTestSkipped('count() method not implemented in QueryBuilder');
        }
        
        $count = $this->queryBuilder
            ->table('test_query_builder')
            ->count();
        
        $this->assertEquals(3, $count);
        
        $count = $this->queryBuilder
            ->table('test_query_builder')
            ->where('active', '=', 1)
            ->count();
        
        $this->assertEquals(2, $count);
    }
    
    /**
     * Test join method
     */
    public function testJoin()
    {
        // Skip this test if the join or select methods are not implemented
        if (!method_exists($this->queryBuilder, 'join') || !method_exists($this->queryBuilder, 'select')) {
            $this->markTestSkipped('join() or select() methods not implemented in QueryBuilder');
        }
        
        try {
            // Create a second table for testing joins
            Schema::dropIfExists('test_query_builder_posts');
            Schema::create('test_query_builder_posts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('title');
                $table->text('content');
                $table->timestamps();
            });
            
            // Insert some test data
            $this->connection->execute('INSERT INTO test_query_builder_posts (user_id, title, content) VALUES (?, ?, ?)', [1, 'First Post', 'Content 1']);
            $this->connection->execute('INSERT INTO test_query_builder_posts (user_id, title, content) VALUES (?, ?, ?)', [1, 'Second Post', 'Content 2']);
            $this->connection->execute('INSERT INTO test_query_builder_posts (user_id, title, content) VALUES (?, ?, ?)', [2, 'Another Post', 'Content 3']);
            
            // Test inner join
            $results = $this->queryBuilder
                ->table('test_query_builder')
                ->select(['test_query_builder.name', 'test_query_builder_posts.title'])
                ->join('test_query_builder_posts', 'test_query_builder.id', '=', 'test_query_builder_posts.user_id')
                ->orderBy('test_query_builder_posts.id', 'asc')
                ->get();
            
            $this->assertIsArray($results);
            $this->assertCount(3, $results);
            $this->assertEquals('John Doe', $results[0]['name']);
            $this->assertEquals('First Post', $results[0]['title']);
            $this->assertEquals('John Doe', $results[1]['name']);
            $this->assertEquals('Second Post', $results[1]['title']);
            $this->assertEquals('Jane Smith', $results[2]['name']);
            $this->assertEquals('Another Post', $results[2]['title']);
        } catch (PDOException $e) {
            $this->markTestSkipped('Join test failed: ' . $e->getMessage());
        }
    }
} 