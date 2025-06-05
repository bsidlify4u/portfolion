# Portfolion Framework Test Suite

This directory contains comprehensive tests for the Portfolion Framework. The tests are organized by component and are designed to validate all aspects of the framework's functionality.

## Test Structure

### Core Components

- **Auth Tests**: Tests for authentication and authorization functionality
  - User authentication
  - Authorization policies
  - Password hashing and verification
  - JWT token handling
  - Session-based and token-based guards
  - User providers and retrievers

- **Cache Tests**: Tests for caching functionality
  - Cache storage and retrieval
  - Cache expiration
  - Different cache drivers (file, redis, memcached)

- **Config Tests**: Tests for configuration functionality
  - Loading configuration files
  - Environment-specific configuration
  - Configuration overrides

- **Console Tests**: Tests for command-line functionality
  - Command registration and execution
  - Command arguments and options
  - Output formatting

- **Container Tests**: Tests for dependency injection container
  - Service registration and resolution
  - Autowiring
  - Contextual binding

- **Core Tests**: Tests for core framework functionality
  - Application lifecycle
  - Service provider registration
  - Environment detection
  - Facade pattern implementation
  - Service location and static proxies
  - Facade accessor resolution

- **Database Tests**: Tests for database functionality
  - Connection management
  - Query building
  - Schema migrations
  - Model ORM functionality
  - Multi-database support

- **Events Tests**: Tests for event system
  - Event registration
  - Event dispatching
  - Event listeners and subscribers

- **Exceptions Tests**: Tests for exception handling
  - Custom exception classes
  - Exception rendering
  - Exception handling middleware

- **Http Tests**: Tests for HTTP functionality
  - Request handling
  - Response generation
  - Cookie management
  - File uploads

- **Logging Tests**: Tests for logging functionality
  - Log message formatting
  - Log storage
  - Log levels

- **Middleware Tests**: Tests for HTTP middleware
  - Middleware registration
  - Middleware execution order
  - Request and response manipulation

- **Queue Tests**: Tests for job queue functionality
  - Job creation and dispatching
  - Job handling and execution
  - Failed job handling
  - Queue drivers (sync, database, redis)
  - Job payloads and serialization
  - Delayed job execution
  - Queue connectors and driver configuration

- **Routing Tests**: Tests for routing functionality
  - Route registration
  - Route matching
  - Route parameters
  - Route groups and middleware

- **Security Tests**: Tests for security features
  - CSRF protection
  - XSS prevention
  - Input validation

- **Session Tests**: Tests for session functionality
  - Session storage
  - Session retrieval
  - Session expiration

- **View Tests**: Tests for view rendering
  - Template rendering
  - View data binding
  - View components and partials

## Test Suites

The framework tests are organized into the following test suites:

- **Unit**: General unit tests for core functionality
- **Feature**: Feature tests that test complete features
- **Http**: Tests for HTTP functionality
- **Core**: Tests for core framework components and facades
- **Queue**: Tests for job queue system
- **Auth**: Tests for authentication and authorization
- **Database**: Tests for database operations
- **Config**: Tests for configuration system
- **Container**: Tests for dependency injection container
- **Routing**: Tests for route handling
- **Cache**: Tests for caching system
- **Session**: Tests for session handling
- **View**: Tests for view rendering

## Running Tests

### Using PHPUnit

To run all tests:

```bash
./vendor/bin/phpunit
```

To run tests for a specific component:

```bash
./vendor/bin/phpunit --testsuite Auth
```

To run a specific test class:

```bash
./vendor/bin/phpunit --filter UserAuthenticationTest
```

### Using the Custom Test Runner

For database-specific tests:

```bash
php run_db_tests.php
```

## Writing Tests

When writing tests for the framework, follow these guidelines:

1. **Use TestCase**: Extend the appropriate base test case for your test type.
   - `Tests\TestCase` for general tests
   - `Tests\Database\DatabaseTestCase` for database tests
   - `Tests\Http\HttpTestCase` for HTTP tests

2. **Isolation**: Make sure tests don't depend on each other and clean up after themselves.

3. **Mocking**: Use PHPUnit's mocking capabilities to isolate the component being tested.

4. **Data Providers**: Use data providers for tests that need to run with different inputs.

5. **Assertions**: Use specific assertions that provide clear error messages.

Example:

```php
public function testFeature()
{
    // Arrange - set up the test
    $component = new Component();
    
    // Act - perform the action being tested
    $result = $component->doSomething();
    
    // Assert - verify the result
    $this->assertEquals('expected', $result);
}
```

## Continuous Integration

The test suite is configured to run automatically on code changes via GitHub Actions. See `.github/workflows/tests.yml` for the configuration.

## Test Coverage

Code coverage reports can be generated with:

```bash
./vendor/bin/phpunit --coverage-html reports/coverage
```

The current test coverage target is 80% for all framework components. 