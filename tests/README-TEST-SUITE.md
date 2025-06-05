# Portfolion Framework Test Suite

## Overview

This test suite provides comprehensive coverage of the Portfolion Framework's database functionality, focusing on ensuring compatibility across multiple database engines. The tests are designed to validate the core database components including connections, schema building, query building, models, and migrations.

## Test Structure

The test suite is organized into the following components:

### 1. Connection Tests (`ConnectionTest.php`)

Tests the database connection functionality:
- Establishing connections to different database engines
- Retrieving the PDO instance
- Executing raw SQL queries
- Transaction support (begin, commit, rollback)
- Driver-specific functionality

### 2. Schema Tests (`SchemaTest.php`)

Tests the schema building functionality:
- Creating tables with various column types
- Adding indexes and constraints
- Modifying existing tables
- Dropping tables
- Database-agnostic SQL generation

### 3. Query Builder Tests (`QueryBuilderTest.php`)

Tests the query building functionality:
- Basic select queries
- Where clauses and conditions
- Ordering and limiting results
- Joins between tables
- Insert operations
- Update operations
- Delete operations
- Aggregate functions

### 4. Model Tests (`ModelTest.php`)

Tests the ORM functionality:
- Model creation and retrieval
- Attribute casting
- Hidden attributes
- Mass assignment protection
- Model relationships
- CRUD operations

### 5. Migration Tests (`MigrationTest.php`)

Tests the migration functionality:
- Running migrations
- Rolling back migrations
- Migration batch management
- Database-agnostic migration support

## Database Engine Coverage

The tests are designed to work with multiple database engines:

1. **MySQL/MariaDB**
   - Tests connection, schema creation, and SQL syntax
   - Validates MySQL-specific features

2. **SQLite**
   - Tests in-memory database functionality
   - Validates SQLite-specific syntax

3. **PostgreSQL**
   - Tests connection and schema creation
   - Validates PostgreSQL-specific features

4. **SQL Server**
   - Tests connection and basic operations
   - Validates SQL Server-specific syntax

5. **Oracle**
   - Tests connection and schema compatibility
   - Validates Oracle-specific features

6. **IBM DB2**
   - Tests connection and basic operations
   - Validates DB2-specific syntax

## Test Utilities

The test suite includes several utilities to facilitate testing:

1. **Bootstrap File** (`bootstrap.php`)
   - Sets up the testing environment
   - Configures database connections
   - Provides helper functions

2. **PHPUnit Configuration** (`phpunit.xml`)
   - Configures test suites and directories
   - Sets environment variables for testing

3. **Test Runner** (`run_db_tests.php`)
   - Provides a custom way to run database tests
   - Supports testing against different database engines

## Extending the Test Suite

To add new tests to the suite:

1. Create a new test class that extends `PHPUnit\Framework\TestCase`
2. Implement test methods that start with `test`
3. Use database-agnostic code that works across different engines
4. Handle database connection failures gracefully
5. Clean up after tests to avoid side effects

Example:

```php
public function testNewFeature()
{
    try {
        $connection = new Connection();
        
        // Test code here...
        
        $this->assertTrue($result);
    } catch (PDOException $e) {
        $this->markTestSkipped('Database connection failed: ' . $e->getMessage());
    }
}
```

## Running the Tests

To run the entire test suite:

```bash
vendor/bin/phpunit
```

To run specific test classes:

```bash
vendor/bin/phpunit --filter ConnectionTest
```

To run tests with a specific database engine:

```bash
DB_CONNECTION=sqlite vendor/bin/phpunit
``` 