<?php
namespace Portfolion\Queue;

use Portfolion\Database\Connection;
use Portfolion\Queue\Jobs\DatabaseJob;
use Portfolion\Queue\Jobs\JobInterface;

class DatabaseQueue extends Queue
{
    /**
     * The database connection instance.
     *
     * @var Connection
     */
    protected Connection $database;

    /**
     * The database table that holds the jobs.
     *
     * @var string
     */
    protected string $table;

    /**
     * Create a new database queue instance.
     *
     * @param Connection $database
     * @param string $table
     * @param string $default
     * @return void
     */
    public function __construct(Connection $database, string $table, string $default = 'default')
    {
        $this->table = $table;
        $this->default = $default;
        $this->database = $database;
    }

    /**
     * Get the size of the queue.
     *
     * @param string|null $queue
     * @return int
     */
    public function size(?string $queue = null): int
    {
        return $this->database->table($this->table)
            ->where('queue', $this->getQueue($queue))
            ->where(function ($query) {
                $query->where('reserved_at', null)
                    ->orWhere('reserved_at', '<', $this->database->raw('CURRENT_TIMESTAMP - INTERVAL 90 SECOND'));
            })
            ->count();
    }

    /**
     * Push a raw payload to the database with a given delay.
     *
     * @param string|null $queue
     * @param string $payload
     * @param int $delay
     * @return mixed
     */
    protected function pushToDatabase(?string $queue, string $payload, int $delay = 0): mixed
    {
        return $this->database->table($this->table)->insertGetId([
            'queue' => $this->getQueue($queue),
            'payload' => $payload,
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => time() + $delay,
            'created_at' => time(),
        ]);
    }

    /**
     * Push multiple jobs to the database.
     *
     * @param string|null $queue
     * @param array $payloads
     * @return mixed
     */
    protected function pushBatchToDatabase(?string $queue, array $payloads): mixed
    {
        $queue = $this->getQueue($queue);
        $now = time();
        
        $records = [];
        
        foreach ($payloads as $payload) {
            $records[] = [
                'queue' => $queue,
                'payload' => $payload,
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => $now,
                'created_at' => $now,
            ];
        }
        
        return $this->database->table($this->table)->insert($records);
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param string|null $queue
     * @return JobInterface|null
     */
    public function pop(?string $queue = null): ?JobInterface
    {
        $queue = $this->getQueue($queue);

        // First, we will attempt to get the next available job for this queue
        // by attempting to acquire a lock on it. If the lock is not obtained
        // then another worker is processing this queue and we'll try again.
        $this->database->beginTransaction();

        if ($job = $this->getNextAvailableJob($queue)) {
            // Update the job as reserved
            $this->markJobAsReserved($job->id);
            
            $this->database->commit();

            return new DatabaseJob(
                $this->database,
                $job,
                $this->connectionName,
                $queue
            );
        }

        $this->database->rollBack();

        return null;
    }

    /**
     * Get the next available job for the queue.
     *
     * @param string $queue
     * @return object|null
     */
    protected function getNextAvailableJob(string $queue): ?object
    {
        return $this->database->table($this->table)
            ->lockForUpdate()
            ->where('queue', $queue)
            ->where(function ($query) {
                $query->where('reserved_at', null)
                    ->orWhere('reserved_at', '<', $this->database->raw('CURRENT_TIMESTAMP - INTERVAL 90 SECOND'));
            })
            ->where('available_at', '<=', time())
            ->orderBy('id', 'asc')
            ->first();
    }

    /**
     * Mark the given job ID as reserved.
     *
     * @param int|string $id
     * @return void
     */
    protected function markJobAsReserved(int|string $id): void
    {
        $this->database->table($this->table)->where('id', $id)->update([
            'reserved_at' => time(),
            'attempts' => $this->database->raw('attempts + 1'),
        ]);
    }

    /**
     * Delete a reserved job from the queue.
     *
     * @param string $queue
     * @param string $id
     * @return void
     */
    public function deleteReserved(string $queue, string $id): void
    {
        $this->database->table($this->table)->where('id', $id)->delete();
    }

    /**
     * Release a reserved job back onto the queue.
     *
     * @param string $queue
     * @param object $job
     * @param int $delay
     * @return mixed
     */
    public function release(string $queue, object $job, int $delay = 0): mixed
    {
        return $this->database->table($this->table)->where('id', $job->id)->update([
            'reserved_at' => null,
            'available_at' => time() + $delay,
        ]);
    }

    /**
     * Get the database connection instance.
     *
     * @return Connection
     */
    public function getDatabase(): Connection
    {
        return $this->database;
    }
}
