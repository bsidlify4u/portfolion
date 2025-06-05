# Core Component Tests

This directory contains tests for the core components of the Portfolion Framework, including fundamental functionality that powers the framework.

## Test Structure

The test suite for the Core components is organized as follows:

- `CoreTest.php`: Tests for the basic framework core functionality
- `ConfigTest.php`: Tests for the configuration system
- `FacadeTest.php`: Tests for the Facade pattern implementation

## Components Tested

The tests in this directory cover the following framework components:

1. **Core Framework**:
   - Basic application setup and bootstrapping
   - Service provider registration and booting
   - Application lifecycle events

2. **Configuration System**:
   - Config file loading and parsing
   - Accessing configuration values
   - Environment-specific configuration

3. **Facades**:
   - Service location through facades
   - Static proxy to underlying services
   - Facade accessor resolution

## Test Approach

The tests in this directory follow these approaches:

1. **Unit Testing**: Test individual components in isolation, mocking dependencies when necessary.

2. **Architecture Testing**: Verify that the architectural patterns are implemented correctly and maintain separation of concerns.

3. **Functional Testing**: Verify that core components work together correctly to provide their intended functionality.

## Testing Facades

The Facade tests (`FacadeTest.php`) specifically focus on:

1. **Service Resolution**: Verify that facades correctly resolve their underlying service instances.

2. **Method Forwarding**: Test that method calls on facades are correctly forwarded to the underlying service.

3. **Singleton Behavior**: Ensure that facades maintain a single instance of their services per facade accessor.

4. **Exception Handling**: Verify that appropriate exceptions are thrown when services cannot be resolved.

## Running the Tests

To run these tests, use the PHPUnit command:

```bash
# Run all core tests
./vendor/bin/phpunit --testsuite Core

# Run a specific test file
./vendor/bin/phpunit tests/Core/FacadeTest.php
```

## Future Improvements

As the Core components evolve, additional tests will be added for:

1. More advanced service container features
2. Service provider testing
3. Application bootstrapping process
4. Framework extension points
5. Hook and plugin systems 