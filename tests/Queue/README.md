# Queue Component Tests

This directory contains tests for the Portfolion Framework Queue component, which is responsible for job queueing and processing.

## Test Structure

The test suite for the Queue component is organized as follows:

- `ShouldQueueInterfaceTest.php`: Tests for the `ShouldQueue` interface
- `PayloadCreatorTest.php`: Tests for the `PayloadCreator` class
- `SyncQueueTest.php`: Tests for the `SyncQueue` implementation
- `Connectors/`: Tests for queue connectors
  - `ConnectorInterfaceTest.php`: Tests for the `ConnectorInterface`
  - `SyncConnectorTest.php`: Tests for the `SyncConnector` implementation

## Components Tested

The tests in this directory cover the following framework components:

1. **Job Interface**: Tests for the `ShouldQueue` interface, which defines the contract for queueable jobs.

2. **Queue Implementations**:
   - `SyncQueue`: Tests for the synchronous queue implementation that executes jobs immediately.
   - Additional queue implementations (Database, Redis, etc.) will be added as they are developed.

3. **Queue Connectors**:
   - `SyncConnector`: Tests for the connector that creates synchronous queue instances.
   - Additional connectors will be added as they are developed.

4. **Queue Utilities**:
   - `PayloadCreator`: Tests for the class that creates payloads for jobs to be stored in queues.

## Test Approach

The tests in this directory follow these approaches:

1. **Interface Testing**: Verify that interfaces define the correct methods and that implementations properly implement these interfaces.

2. **Unit Testing**: Test individual components in isolation, mocking dependencies when necessary.

3. **Integration Testing**: Test that different queue components work together correctly (e.g., connectors creating the right queue types).

4. **Functional Testing**: Verify that queues properly handle jobs, execute them, and manage their lifecycle.

## Running the Tests

To run these tests, use the PHPUnit command:

```bash
# Run all queue tests
./vendor/bin/phpunit --testsuite Queue

# Run a specific test file
./vendor/bin/phpunit tests/Queue/SyncQueueTest.php
```

## Future Improvements

As the Queue component evolves, additional tests will be added for:

1. More queue driver implementations (Database, Redis, etc.)
2. Failed job handling and retry mechanisms
3. Job batching and chaining
4. Event dispatching during job lifecycle
5. Queue worker functionality 