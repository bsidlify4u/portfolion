# Portfolion Framework Comprehensive Test Suite

This document provides an overview of the comprehensive test suite for the Portfolion Framework. The tests cover all aspects of the framework's functionality, ensuring reliable and consistent behavior across all components.

## Test Organization

The test suite is organized by framework components, with each component having its own directory of tests:

- **Auth**: Tests for authentication and authorization functionality
- **Cache**: Tests for caching mechanism
- **Config**: Tests for configuration handling
- **Console**: Tests for command-line interface
- **Container**: Tests for dependency injection container
- **Core**: Tests for core framework functionality
- **Database**: Tests for database functionality and ORM
- **Events**: Tests for event dispatching system
- **Exceptions**: Tests for exception handling
- **Http**: Tests for HTTP request/response handling
- **Logging**: Tests for logging functionality
- **Middleware**: Tests for HTTP middleware
- **Queue**: Tests for job queuing
- **Routing**: Tests for routing system
- **Security**: Tests for security features
- **Session**: Tests for session handling
- **View**: Tests for template rendering

## Test Structure

Each component's tests follow a consistent structure:

1. **Unit Tests**: Testing individual classes and methods in isolation
2. **Integration Tests**: Testing how components work together
3. **Feature Tests**: Testing complete features from end to end

## Base Test Classes

The test suite includes several base test classes to provide common functionality:

1. **TestCase**: Base class for all tests, providing utility methods and assertions
2. **DatabaseTestCase**: Base class for database tests, handling connections and setup
3. **HttpTestCase**: Base class for HTTP tests, providing request/response utilities

## Test Utilities

The test suite includes utilities for common testing tasks:

1. **Mock Objects**: Methods for creating mock objects for dependencies
2. **Reflection Helpers**: Methods for testing protected and private properties/methods
3. **Assertion Helpers**: Custom assertions for common testing scenarios

## Continuous Integration

The test suite is integrated with GitHub Actions for continuous testing:

1. **Multiple PHP Versions**: Tests run on PHP 8.0, 8.1, and 8.2
2. **Multiple Database Engines**: Tests run against SQLite and MySQL
3. **Code Coverage**: Code coverage reports are generated and uploaded to Codecov

## Test Examples

Here are examples of the types of tests included for each component:

### Auth Component

- Testing user login with valid and invalid credentials
- Testing user registration
- Testing password hashing and verification
- Testing authentication checks
- Testing remember me functionality

### Container Component

- Testing binding and resolving dependencies
- Testing singleton instances
- Testing automatic resolution of class dependencies
- Testing contextual binding

### Routing Component

- Testing route registration and matching
- Testing route parameters and constraints
- Testing route groups and middleware
- Testing named routes
- Testing RESTful resource routes

### Database Component

- Testing connections to different database engines
- Testing query building and execution
- Testing schema migrations
- Testing model ORM functionality
- Testing database transactions

## Running Tests

### Running All Tests

```bash
vendor/bin/phpunit
```

### Running Tests for a Specific Component

```bash
vendor/bin/phpunit --testsuite Auth
```

### Running a Specific Test Class

```bash
vendor/bin/phpunit --filter RouterTest
```

### Running with Code Coverage

```bash
vendor/bin/phpunit --coverage-html reports/coverage
```

## Writing New Tests

When adding new functionality to the framework, follow these guidelines for writing tests:

1. **Test Location**: Place tests in the appropriate component directory
2. **Test Naming**: Name test classes with a `Test` suffix (e.g., `RouterTest`)
3. **Test Methods**: Name test methods with a `test` prefix (e.g., `testRouteMatching`)
4. **Assertions**: Use specific assertions that provide clear error messages
5. **Isolation**: Ensure tests don't depend on each other and clean up after themselves
6. **Documentation**: Document the purpose of each test method with comments

## Code Coverage Targets

The framework aims for high code coverage to ensure reliability:

- **Overall Target**: 80% code coverage for the entire framework
- **Critical Components**: 90% code coverage for critical components (Auth, Database, Routing)
- **Edge Cases**: Tests should cover both normal usage and edge cases

## Conclusion

The comprehensive test suite ensures that the Portfolion Framework functions correctly across all components and provides a solid foundation for building reliable applications. By maintaining high test coverage and continuously running tests, we can catch and fix issues before they affect users. 