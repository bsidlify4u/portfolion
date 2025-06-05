# Portfolion Testing Guide

This document provides information about Portfolion's testing infrastructure and how to write effective tests for the framework.

## Testing Infrastructure

Portfolion uses PHPUnit for testing and has a robust testing infrastructure that includes:

1. **Test Database Support**: Tests use an in-memory SQLite database by default for speed and isolation, with fallback to MySQL when needed
2. **Environment Configuration**: Testing-specific environment configuration
3. **Helper Classes**: Utilities to set up test environments consistently
4. **Database Seeding**: Automatic seeding of test data
5. **Mock Objects**: Mock implementations for external dependencies like Redis

## Running Tests

### Using the Test Command

The easiest way to run tests is using the built-in test command:

```bash
php portfolion test                    # Run all tests
php portfolion test Unit               # Run Unit tests only
php portfolion test Database           # Run Database tests only
php portfolion test --filter=TestName  # Run tests matching a filter
php portfolion test --stop-on-failure  # Stop on first failure
php portfolion test --coverage         # Generate code coverage report
```

### Using PHPUnit Directly

You can also run PHPUnit directly:

```bash
php vendor/bin/phpunit
php vendor/bin/phpunit --testsuite Unit
php vendor/bin/phpunit --testsuite Feature
php vendor/bin/phpunit --testsuite Database
```

## Test Organization

Tests are organized into the following categories:

1. **Unit Tests**: Tests for individual classes and methods
2. **Feature Tests**: Tests for end-to-end functionality
3. **Database Tests**: Tests for database interactions
4. **Http Tests**: Tests for HTTP requests and responses
5. **Integration Tests**: Tests for component interactions

## Writing Tests

### Base Test Case

All tests should extend the base `Tests\TestCase` class, which provides common functionality:

```php
use Tests\TestCase;

class MyTest extends TestCase
{
    public function testSomething(): void
    {
        // Your test code here
    }
}
```

### Database Tests

For tests that interact with the database, extend the `Tests\Database\DatabaseTestCase` class:

```php
use Tests\Database\DatabaseTestCase;

class MyDatabaseTest extends DatabaseTestCase
{
    public function testDatabaseInteraction(): void
    {
        // Database will be automatically set up with test tables
        $result = $this->connection->select('SELECT * FROM users');
        $this->assertNotEmpty($result);
    }
}
```

#### Database Driver Compatibility

When writing database tests, be aware that the test environment may be using either SQLite or MySQL. The framework will detect the available database driver and use it accordingly. However, there are syntax differences between database systems that need to be handled in your tests:

```php
public function setUp(): void
{
    parent::setUp();
    
    // Get the database driver to use appropriate SQL syntax
    $this->driver = $this->connection->getDriver();
    
    // Create a test table with driver-specific SQL
    $createTableSql = $this->getCreateTableSql('test_table');
    
    $this->connection->execute($createTableSql);
}

protected function getCreateTableSql(string $tableName): string
{
    switch ($this->driver) {
        case 'sqlite':
            return "CREATE TABLE {$tableName} (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT UNIQUE,
                active INTEGER DEFAULT 1
            )";
            
        case 'mysql':
        case 'mariadb':
            return "CREATE TABLE {$tableName} (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE,
                active TINYINT(1) DEFAULT 1
            )";
            
        case 'pgsql':
            return "CREATE TABLE {$tableName} (
                id SERIAL PRIMARY KEY,
                name TEXT NOT NULL,
                email TEXT UNIQUE,
                active BOOLEAN DEFAULT TRUE
            )";
            
        default:
            throw new RuntimeException("Unsupported database driver: {$this->driver}");
    }
}
```

#### Handling Boolean Values

Be especially careful with boolean values, as they are handled differently across database systems:

- **SQLite**: Uses 0 and 1 for boolean values
- **MySQL**: Uses TINYINT(1) with 0 and 1 values
- **PostgreSQL**: Has a native boolean type

When writing tests that involve boolean fields:

```php
// When inserting data
$active = ($this->driver === 'pgsql') ? true : 1;
$this->connection->execute(
    "INSERT INTO users (name, email, active) VALUES (?, ?, ?)",
    ['John', 'john@example.com', $active]
);

// When updating data
$active = ($this->driver === 'pgsql') ? false : 0;
$this->connection->execute(
    "UPDATE users SET active = ? WHERE email = ?",
    [$active, 'john@example.com']
);
```

### Test Environment Helper

The `Tests\Helpers\TestEnvironment` class provides utility methods for setting up test environments:

```php
use Tests\TestCase;
use Tests\Helpers\TestEnvironment;
use Portfolion\Database\Connection;

class MyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up testing environment
        TestEnvironment::setupTestConfig();
        TestEnvironment::setupDatabaseForTesting();
        
        // Create a connection and test tables
        $connection = new Connection();
        TestEnvironment::createTestTables($connection);
    }
}
```

### Mocking External Services

For tests that require external services like Redis, use the mock helpers:

```php
use Tests\TestCase;
use Tests\Helpers\TestEnvironment;
use Portfolion\Config\Drivers\RedisDriver;

class RedisTest extends TestCase
{
    public function testRedisDriver(): void
    {
        // Create a mock Redis object
        $redisMock = TestEnvironment::createRedisMock($this);
        
        // Use the mock in your test
        $driver = new RedisDriver($redisMock);
        // Test with the mock driver
    }
}
```

## Best Practices

1. **Use In-Memory SQLite**: For database tests, prefer using the in-memory SQLite database for speed when possible
2. **Handle Multiple Database Drivers**: Write database tests that can work with both SQLite and MySQL
3. **Isolate Tests**: Each test should be independent and not rely on the state from other tests
4. **Use Mocks**: Mock external dependencies to avoid relying on external services
5. **Clean Up**: Clean up any resources created during tests in the `tearDown` method
6. **Use Factories**: Use factory methods to create test data instead of hardcoding it
7. **Test Edge Cases**: Include tests for edge cases and error conditions
8. **Error Handling**: Use try-catch blocks to handle expected exceptions and skip tests gracefully when necessary
9. **Boolean Values**: Always use explicit 0/1 values for boolean fields for MySQL compatibility

## Continuous Integration

The test suite is run automatically on each pull request and merge to the main branch. All tests must pass before code can be merged.

## Test Coverage

Aim for high test coverage, especially for critical components. You can generate a test coverage report with:

```bash
php portfolion test --coverage
```

This will create an HTML coverage report in the `reports/coverage` directory. 

## Troubleshooting Common Issues

### Database Driver Compatibility

If your tests work with SQLite but fail with MySQL:

1. Check boolean field handling (use 0/1 instead of false/true)
2. Verify SQL syntax is compatible with both drivers
3. Check for SQLite-specific features that don't work in MySQL
4. Ensure proper error handling for driver-specific exceptions

### Memory Issues

If tests are consuming too much memory:

1. Use smaller datasets for testing
2. Clean up resources in tearDown methods
3. Use separate test methods instead of large test cases
4. Consider using the `--memory-limit` option with PHPUnit 