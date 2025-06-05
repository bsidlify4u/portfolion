# Portfolion Framework Features

This document describes the production-ready features implemented in the Portfolion PHP framework.

## Cache System

The Portfolion Framework includes a robust caching system with support for multiple cache drivers:

### Cache Drivers

- **File Cache**: Default driver that stores cache data in the filesystem
- **Redis Cache**: For high-performance caching using Redis
- **Memcached Cache**: For distributed caching using Memcached

### Usage

```php
// Get cache instance
$cache = \Portfolion\Cache\Cache::getInstance();

// Basic operations
$cache->put('key', 'value', 60); // Store for 60 seconds
$value = $cache->get('key', 'default'); // Get with default fallback
$exists = $cache->has('key'); // Check if key exists
$cache->forget('key'); // Remove from cache
$cache->flush(); // Clear all cache

// Remember pattern
$value = $cache->remember('key', 60, function() {
    return expensiveOperation();
});

// Increment/Decrement
$cache->increment('counter');
$cache->decrement('counter');

// Tagging
$cache->tags(['tag1', 'tag2'])->put('key', 'value', 60);
$value = $cache->tags(['tag1', 'tag2'])->get('key');
$cache->tags(['tag1'])->flush(); // Flush all entries with tag1
```

### Configuration

Configure caching in `config/cache.php`:

```php
// Default driver
'default' => env('CACHE_DRIVER', 'file'),

// File cache settings
'stores' => [
    'file' => [
        'driver' => 'file',
        'path' => storage_path('framework/cache'),
    ],
    // Redis, Memcached, etc. configurations
]
```

## Queue System

The framework provides a powerful queue system for handling background jobs:

### Queue Drivers

- **Database**: Uses a database table to store jobs
- **Redis**: Uses Redis for job storage
- **SQS**: Amazon SQS integration for cloud-based queuing
- **Sync**: Executes jobs immediately (useful for testing)

### Creating Jobs

```php
use Portfolion\Queue\Job;

class SendEmailJob extends Job
{
    protected $user;
    protected $message;
    
    public function __construct($user, $message)
    {
        $this->user = $user;
        $this->message = $message;
    }
    
    public function handle()
    {
        // Send email logic
        mail($this->user->email, 'Subject', $this->message);
    }
}
```

### Dispatching Jobs

```php
// Dispatch a job to the default queue
Queue::push(new SendEmailJob($user, 'Hello!'));

// Dispatch to a specific queue
Queue::push(new SendEmailJob($user, 'Hello!'), 'emails');

// Delay execution by 60 seconds
Queue::later(new SendEmailJob($user, 'Hello!'), 60);
```

### Processing Jobs

Run the queue worker:

```bash
php portfolion queue:work
```

Options:
- `--queue=default`: Specify the queue to process
- `--connection=database`: Specify the connection to use
- `--once`: Process a single job and exit
- `--sleep=3`: Sleep time when no jobs are available
- `--tries=3`: Number of times to attempt a job before logging it as failed
- `--timeout=60`: The number of seconds a child process can run

### Configuration

Configure queues in `config/queue.php`:

```php
// Default queue connection
'default' => env('QUEUE_CONNECTION', 'database'),

// Connections configuration
'connections' => [
    'database' => [
        'driver' => 'database',
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90,
    ],
    // Redis, SQS, etc. configurations
]
```

## Commands

The framework provides several commands for working with these features:

### Cache Commands
- `php portfolion cache:test`: Test cache functionality

### Queue Commands
- `php portfolion queue:migrate`: Create queue database tables
- `php portfolion queue:work`: Process jobs from the queue
- `php portfolion queue:dispatch`: Dispatch a job to the queue

## Implementation Details

Both the cache and queue systems are designed with the following principles:

1. **Abstraction**: Common interfaces for different implementations
2. **Configuration**: Easily configurable through environment variables and config files
3. **Performance**: Optimized for performance and reliability
4. **Flexibility**: Support for multiple drivers to fit different needs
5. **Error Handling**: Robust error handling and recovery mechanisms 