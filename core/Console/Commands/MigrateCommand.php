<?php

namespace Portfolion\Console\Commands;

use Portfolion\Database\Migrations\Migrator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateCommand extends BaseCommand
{
    /**
     * The name of the console command.
     */
    protected static $defaultName = 'migrate';

    /**
     * The console command description.
     */
    protected static $defaultDescription = 'Run the database migrations';

    /**
     * Configure the command options.
     */
    protected function configure(): void
    {
        $this->addOption('step', null, InputOption::VALUE_REQUIRED, 'The number of migrations to run');
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Force the operation to run in production');
        $this->addOption('pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run');
        $this->addOption('seed', null, InputOption::VALUE_NONE, 'Run the database seeders after migration');
        $this->addOption('refresh', null, InputOption::VALUE_NONE, 'Refresh the database by rolling back all migrations and re-running them');
        $this->addOption('rollback', null, InputOption::VALUE_NONE, 'Rollback the last batch of migrations');
        $this->addOption('reset', null, InputOption::VALUE_NONE, 'Rollback all database migrations');
    }

    /**
     * Execute the console command.
     */
    protected function handle(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        // Check if in production without force flag
        if ($this->isProduction() && !$input->getOption('force')) {
            $io->error('Application is in production. Use --force to run migrations.');
            return Command::FAILURE;
        }

        $migrator = new Migrator();
        
        // Handle different migration commands based on options
        if ($input->getOption('reset')) {
            return $this->resetDatabase($migrator, $io);
        }
        
        if ($input->getOption('rollback')) {
            return $this->rollbackMigrations($migrator, $io);
        }
        
        if ($input->getOption('refresh')) {
            return $this->refreshDatabase($migrator, $io);
        }
        
        // Run migrations
        $steps = $input->getOption('step') ? (int) $input->getOption('step') : null;
        
        $io->title('Running Migrations');
        
        try {
            $migrations = $migrator->run($steps);
            
            if (empty($migrations)) {
                $io->info('Nothing to migrate. Database is already up to date.');
            } else {
                $io->success(count($migrations) . ' migration(s) completed successfully.');
                
                foreach ($migrations as $migration) {
                    $io->text('✓ ' . $migration);
                }
            }
            
            // Run seeders if requested
            if ($input->getOption('seed')) {
                $this->runSeeders($io);
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Migration failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Rollback migrations.
     */
    protected function rollbackMigrations(Migrator $migrator, SymfonyStyle $io): int
    {
        $io->title('Rolling Back Migrations');
        
        try {
            $migrations = $migrator->rollback();
            
            if (empty($migrations)) {
                $io->info('Nothing to rollback.');
            } else {
                $io->success(count($migrations) . ' migration(s) rolled back successfully.');
                
                foreach ($migrations as $migration) {
                    $io->text('✓ ' . $migration);
                }
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Rollback failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Reset the database by rolling back all migrations.
     */
    protected function resetDatabase(Migrator $migrator, SymfonyStyle $io): int
    {
        $io->title('Resetting Database');
        
        try {
            $migrations = $migrator->reset();
            
            if (empty($migrations)) {
                $io->info('Nothing to reset.');
            } else {
                $io->success(count($migrations) . ' migration(s) rolled back successfully.');
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Reset failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Refresh the database by resetting and re-running all migrations.
     */
    protected function refreshDatabase(Migrator $migrator, SymfonyStyle $io): int
    {
        // First reset the database
        $resetResult = $this->resetDatabase($migrator, $io);
        
        if ($resetResult !== Command::SUCCESS) {
            return $resetResult;
        }
        
        // Then run the migrations
        $io->title('Re-running Migrations');
        
        try {
            $migrations = $migrator->run();
            
            if (empty($migrations)) {
                $io->info('No migrations to run.');
            } else {
                $io->success(count($migrations) . ' migration(s) completed successfully.');
                
                foreach ($migrations as $migration) {
                    $io->text('✓ ' . $migration);
                }
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Migration failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Run the database seeders.
     */
    protected function runSeeders(SymfonyStyle $io): void
    {
        $io->title('Running Database Seeders');
        
        // Create a new instance of the seed command and run it
        $command = $this->getApplication()->find('db:seed');
        $command->run(new \Symfony\Component\Console\Input\ArrayInput([]), $io);
    }
    
    /**
     * Check if the application is in production environment.
     */
    protected function isProduction(): bool
    {
        return env('APP_ENV') === 'production';
    }
} 