<?php

namespace App\Jobs;

use Portfolion\Queue\Job;
use Portfolion\Logging\Logger;

class ExampleJob extends Job
{
    /**
     * @var string The data to process
     */
    protected $data;
    
    /**
     * Create a new job instance.
     *
     * @param string $data The data to process
     * @return void
     */
    public function __construct(string $data)
    {
        $this->data = $data;
    }
    
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        // Log the job execution
        Logger::info('Processing job with data: ' . $this->data);
        
        // Simulate some work
        sleep(2);
        
        // Log the job completion
        Logger::info('Job completed: ' . $this->data);
    }
    
    /**
     * Handle a job failure.
     *
     * @param \Exception $exception The exception that caused the failure
     * @return void
     */
    public function failed(\Exception $exception): void
    {
        // Log the job failure
        Logger::error('Job failed: ' . $this->data . ' - ' . $exception->getMessage());
    }
} 