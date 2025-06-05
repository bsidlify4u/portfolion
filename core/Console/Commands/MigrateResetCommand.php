<?php

namespace Portfolion\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Portfolion\Database\Connection;
use Portfolion\Database\Schema\Schema;

#[AsCommand(
    name: 'migrate:reset',
    description: 'Reset all database tables'
)]
class MigrateResetCommand extends BaseCommand
{
    /**
     * @var Connection Database connection
     */
    protected $connection;
    
    /**
     * Create a new migrate reset command instance
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
        $output->writeln("<i>Resetting database tables...</i>");
        
        try {
            // Drop all tables
            $this->dropAllTables($output);
            
            // Re-run migrations
            $output->writeln("<i>Re-running migrations...</i>");
            $migrateCommand = new MigrateCommand();
            $migrateCommand->handle($input, $output);
            
            $this->success($output, "Database reset completed successfully!");
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error($output, "Database reset failed: " . $e->getMessage());
            $output->writeln("<e>Stack trace:</e>");
            $output->writeln($e->getTraceAsString());
            return self::FAILURE;
        }
    }
    
    /**
     * Drop all tables in the database
     * 
     * @param OutputInterface $output Command output
     * @return void
     */
    protected function dropAllTables(OutputInterface $output): void
    {
        $pdo = $this->connection->getPdo();
        $driver = $this->connection->getDriver();
        
        // Get a list of all tables
        $tables = [];
        
        if ($driver === 'mysql') {
            $stmt = $pdo->query('SHOW TABLES');
            while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
        } elseif ($driver === 'sqlite') {
            $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name != 'sqlite_sequence'");
            while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
        } else {
            throw new \RuntimeException("Unsupported database driver: {$driver}");
        }
        
        // Drop each table
        foreach ($tables as $table) {
            if ($table === 'migrations') {
                continue; // Skip the migrations table
            }
            
            $output->writeln("  Dropping table: <info>{$table}</info>");
            Schema::dropIfExists($table);
        }
        
        $output->writeln("<s>All tables dropped successfully.</s>");
    }
} 