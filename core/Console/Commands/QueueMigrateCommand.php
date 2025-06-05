<?php

namespace Portfolion\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Portfolion\Database\Schema\Schema;
use Portfolion\Database\Schema\Blueprint;
use Portfolion\Database\Connection;

#[AsCommand(
    name: 'queue:migrate',
    description: 'Run the queue database migrations'
)]
class QueueMigrateCommand extends BaseCommand
{
    /**
     * @var Connection Database connection
     */
    protected $connection;
    
    /**
     * Create a new queue migrate command instance
     */
    public function __construct()
    {
        parent::__construct();
        $this->connection = new Connection();
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
        $output->writeln("<i>Running queue migrations...</i>");
        
        try {
            // Create jobs table
            $output->writeln("Creating jobs table...");
            $this->createJobsTable();
            $output->writeln("<s>Jobs table created successfully.</s>");
            
            // Create failed jobs table
            $output->writeln("Creating failed jobs table...");
            $this->createFailedJobsTable();
            $output->writeln("<s>Failed jobs table created successfully.</s>");
            
            $this->success($output, "Queue migrations completed successfully!");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error($output, "Queue migration failed: " . $e->getMessage());
            $output->writeln("<e>Stack trace:</e>");
            $output->writeln($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
    
    /**
     * Create the jobs table
     * 
     * @return void
     */
    protected function createJobsTable(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
            $table->index(['queue', 'reserved_at']);
        });
    }
    
    /**
     * Create the failed jobs table
     * 
     * @return void
     */
    protected function createFailedJobsTable(): void
    {
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }
} 