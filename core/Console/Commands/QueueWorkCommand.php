<?php

namespace Portfolion\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Portfolion\Queue\Worker;

#[AsCommand(
    name: 'queue:work',
    description: 'Process jobs from the queue'
)]
class QueueWorkCommand extends BaseCommand
{
    /**
     * @var Worker Queue worker instance
     */
    protected $worker;
    
    /**
     * Create a new queue work command instance
     * 
     * @param Worker $worker Queue worker instance
     */
    public function __construct(Worker $worker = null)
    {
        parent::__construct();
        
        // Create a worker instance if none provided
        if ($worker === null) {
            try {
                $this->worker = new Worker();
            } catch (\Exception $e) {
                // Log the error but continue - the command will fail gracefully later
                error_log("Failed to create Worker: " . $e->getMessage());
                $this->worker = null;
            }
        } else {
            $this->worker = $worker;
        }
    }
    
    /**
     * Configure the command
     * 
     * @return void
     */
    protected function configure(): void
    {
        $this->addOption('queue', 'q', InputOption::VALUE_REQUIRED, 'The queue to process', 'default');
        $this->addOption('connection', 'c', InputOption::VALUE_REQUIRED, 'The queue connection to use', 'default');
        $this->addOption('once', null, InputOption::VALUE_NONE, 'Only process a single job');
        $this->addOption('sleep', null, InputOption::VALUE_REQUIRED, 'Number of seconds to sleep when no job is available', 3);
        $this->addOption('tries', null, InputOption::VALUE_REQUIRED, 'Number of times to attempt a job before logging it failed', 0);
        $this->addOption('timeout', null, InputOption::VALUE_REQUIRED, 'The number of seconds a child process can run', 60);
    }
    
    /**
     * Execute the command
     * 
     * @param InputInterface $input Command input
     * @param OutputInterface $output Command output
     * @return int Command exit code
     */
    protected function handle(InputInterface $input, OutputInterface $output): int
    {
        // Check if worker was successfully created
        if ($this->worker === null) {
            $output->writeln("<e>Error: Queue worker could not be initialized. Check the application configuration.</e>");
            return Command::FAILURE;
        }
        
        $queue = $input->getOption('queue');
        $connection = $input->getOption('connection');
        $once = $input->getOption('once');
        $sleep = (int) $input->getOption('sleep');
        $tries = (int) $input->getOption('tries');
        $timeout = (int) $input->getOption('timeout');
        
        $this->listenForSignals();
        
        if ($once) {
            $output->writeln("<i>Processing jobs from the [{$queue}] queue (one-time)...</i>");
            $this->worker->runNextJob($connection, $queue, $timeout, $sleep, $tries);
        } else {
            $output->writeln("<i>Processing jobs from the [{$queue}] queue...</i>");
            $this->worker->daemon($connection, $queue, $timeout, $sleep, $tries);
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Listen for signals to stop the worker
     * 
     * @return void
     */
    protected function listenForSignals(): void
    {
        if (extension_loaded('pcntl')) {
            pcntl_async_signals(true);
            
            // Handle termination signals
            pcntl_signal(SIGTERM, function () {
                $this->worker->stop();
            });
            
            pcntl_signal(SIGINT, function () {
                $this->worker->stop();
            });
            
            pcntl_signal(SIGQUIT, function () {
                $this->worker->stop();
            });
        }
    }
} 