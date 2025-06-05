<?php

namespace Portfolion\Queue;

use Aws\Sqs\SqsClient;
use RuntimeException;

/**
 * AWS SQS queue driver for the Portfolion framework
 */
class SqsQueue implements QueueInterface
{
    /**
     * @var SqsClient SQS client
     */
    protected $sqs;
    
    /**
     * @var array Configuration
     */
    protected $config;
    
    /**
     * @var array Queue URLs
     */
    protected $queueUrls = [];
    
    /**
     * Create a new SQS queue instance
     * 
     * @param mixed $config Configuration
     */
    public function __construct($config)
    {
        $this->config = $config;
        
        // Create the SQS client
        $this->sqs = $this->createClient();
    }
    
    /**
     * Push a job onto the queue
     * 
     * @param array $job The job to push
     * @return bool Whether the operation was successful
     */
    public function push(array $job): bool
    {
        $queue = $job['queue'];
        $payload = json_encode($job);
        
        try {
            $this->sqs->sendMessage([
                'QueueUrl' => $this->getQueueUrl($queue),
                'MessageBody' => $payload,
                'DelaySeconds' => max(0, (int) (($job['available_at'] ?? time()) - time())),
            ]);
            
            return true;
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }
    
    /**
     * Pop a job off the queue
     * 
     * @param string $queue The queue to pop from
     * @return mixed The job or null
     */
    public function pop(string $queue)
    {
        try {
            $response = $this->sqs->receiveMessage([
                'QueueUrl' => $this->getQueueUrl($queue),
                'AttributeNames' => ['ApproximateReceiveCount'],
                'MaxNumberOfMessages' => 1,
                'VisibilityTimeout' => 60, // 1 minute
            ]);
            
            if (!isset($response['Messages']) || empty($response['Messages'])) {
                return null;
            }
            
            $message = $response['Messages'][0];
            $job = json_decode($message['Body'], true);
            
            // Add SQS-specific metadata
            $job['id'] = $message['MessageId'];
            $job['receipt_handle'] = $message['ReceiptHandle'];
            $job['attempts'] = (int) ($message['Attributes']['ApproximateReceiveCount'] ?? 1);
            $job['reserved_at'] = time();
            
            return $job;
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return null;
        }
    }
    
    /**
     * Delete a job from the queue
     * 
     * @param mixed $job The job to delete
     * @return bool Whether the operation was successful
     */
    public function delete($job): bool
    {
        try {
            $this->sqs->deleteMessage([
                'QueueUrl' => $this->getQueueUrl($job['queue']),
                'ReceiptHandle' => $job['receipt_handle'],
            ]);
            
            return true;
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }
    
    /**
     * Release a job back onto the queue
     * 
     * @param mixed $job The job to release
     * @param int $delay The delay in seconds
     * @return bool Whether the operation was successful
     */
    public function release($job, int $delay = 0): bool
    {
        try {
            $this->sqs->changeMessageVisibility([
                'QueueUrl' => $this->getQueueUrl($job['queue']),
                'ReceiptHandle' => $job['receipt_handle'],
                'VisibilityTimeout' => $delay,
            ]);
            
            return true;
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }
    
    /**
     * Push multiple jobs onto the queue
     * 
     * @param array $jobs The jobs to push
     * @return bool Whether the operation was successful
     */
    public function bulk(array $jobs): bool
    {
        try {
            // Group jobs by queue
            $jobsByQueue = [];
            foreach ($jobs as $job) {
                $queue = $job['queue'];
                if (!isset($jobsByQueue[$queue])) {
                    $jobsByQueue[$queue] = [];
                }
                $jobsByQueue[$queue][] = $job;
            }
            
            // Send jobs in batches of 10 (SQS limit)
            foreach ($jobsByQueue as $queue => $queueJobs) {
                $batches = array_chunk($queueJobs, 10);
                
                foreach ($batches as $batch) {
                    $entries = [];
                    
                    foreach ($batch as $index => $job) {
                        $entries[] = [
                            'Id' => (string) $index,
                            'MessageBody' => json_encode($job),
                            'DelaySeconds' => max(0, (int) (($job['available_at'] ?? time()) - time())),
                        ];
                    }
                    
                    $this->sqs->sendMessageBatch([
                        'QueueUrl' => $this->getQueueUrl($queue),
                        'Entries' => $entries,
                    ]);
                }
            }
            
            return true;
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }
    
    /**
     * Get the size of a queue
     * 
     * @param string $queue The queue name
     * @return int The queue size
     */
    public function size(string $queue): int
    {
        try {
            $response = $this->sqs->getQueueAttributes([
                'QueueUrl' => $this->getQueueUrl($queue),
                'AttributeNames' => ['ApproximateNumberOfMessages'],
            ]);
            
            return (int) ($response['Attributes']['ApproximateNumberOfMessages'] ?? 0);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return 0;
        }
    }
    
    /**
     * Clear a queue
     * 
     * @param string $queue The queue name
     * @return bool Whether the operation was successful
     */
    public function clear(string $queue): bool
    {
        try {
            $this->sqs->purgeQueue([
                'QueueUrl' => $this->getQueueUrl($queue),
            ]);
            
            return true;
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }
    
    /**
     * Get the URL for a queue
     * 
     * @param string $queue The queue name
     * @return string The queue URL
     */
    protected function getQueueUrl(string $queue): string
    {
        if (!isset($this->queueUrls[$queue])) {
            $prefix = $this->config->get('queue.sqs.prefix', '');
            $queueName = $prefix . $queue;
            
            try {
                $result = $this->sqs->getQueueUrl(['QueueName' => $queueName]);
                $this->queueUrls[$queue] = $result['QueueUrl'];
            } catch (\Exception $e) {
                // Queue doesn't exist, create it
                $result = $this->sqs->createQueue(['QueueName' => $queueName]);
                $this->queueUrls[$queue] = $result['QueueUrl'];
            }
        }
        
        return $this->queueUrls[$queue];
    }
    
    /**
     * Create an SQS client
     * 
     * @return SqsClient The SQS client
     * @throws RuntimeException If the client cannot be created
     */
    protected function createClient(): SqsClient
    {
        try {
            return new SqsClient([
                'version' => 'latest',
                'region' => $this->config->get('queue.sqs.region', 'us-east-1'),
                'credentials' => [
                    'key' => $this->config->get('queue.sqs.key'),
                    'secret' => $this->config->get('queue.sqs.secret'),
                ],
            ]);
        } catch (\Exception $e) {
            throw new RuntimeException('SQS client creation failed: ' . $e->getMessage());
        }
    }
}