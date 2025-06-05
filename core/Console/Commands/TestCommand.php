<?php
namespace Portfolion\Console\Commands;

use Portfolion\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tests\Helpers\TestEnvironment;

class TestCommand extends Command
{
    /**
     * The name of the command
     *
     * @var string
     */
    protected string $name = 'test';
    
    /**
     * The description of the command
     *
     * @var string
     */
    protected string $description = 'Run PHPUnit tests';
    
    /**
     * Run the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    public function run(InputInterface $input, OutputInterface $output): int
    {
        global $argv;
        
        // Extract arguments from the command line directly
        $args = [];
        
        // Find the position of the test command
        $cmdPos = array_search('test', $argv);
        if ($cmdPos !== false && isset($argv[$cmdPos + 1]) && strpos($argv[$cmdPos + 1], '-') !== 0) {
            // The argument after 'test' is the suite name if it doesn't start with -
            $args['suite'] = $argv[$cmdPos + 1];
        }
        
        // Process options
        foreach ($argv as $arg) {
            if (strpos($arg, '--filter=') === 0) {
                $args['--filter'] = substr($arg, 9);
            } elseif ($arg === '--stop-on-failure') {
                $args['--stop-on-failure'] = true;
            } elseif ($arg === '--no-coverage') {
                $args['--no-coverage'] = true;
            } elseif ($arg === '--coverage') {
                $args['--coverage'] = true;
            }
        }
        
        // Execute the command
        return $this->execute($args);
    }
    
    /**
     * Execute the command
     *
     * @param array $args Command arguments
     * @return int 0 if everything went fine, or an exit code
     */
    public function execute(array $args): int
    {
        // Parse arguments
        $suite = $args['suite'] ?? null;
        $filter = $args['--filter'] ?? null;
        $stopOnFailure = isset($args['--stop-on-failure']) && $args['--stop-on-failure'];
        $noCoverage = isset($args['--no-coverage']) && $args['--no-coverage'];
        $withCoverage = isset($args['--coverage']) && $args['--coverage'];
        
        // Default to no coverage unless explicitly requested
        $generateCoverage = $withCoverage && !$noCoverage;
        
        // Ensure we're in testing mode
        $this->info('Setting up test environment...');
        TestEnvironment::setupTestConfig();
        TestEnvironment::setupDatabaseForTesting();
        
        // Build command
        $command = ['./vendor/bin/phpunit'];
        
        // Add suite if specified
        if ($suite) {
            $command[] = "--testsuite {$suite}";
        }
        
        // Add filter if specified
        if ($filter) {
            $command[] = "--filter \"{$filter}\"";
        }
        
        // Add stop on failure if specified
        if ($stopOnFailure) {
            $command[] = '--stop-on-failure';
        }
        
        // Add coverage options only if explicitly requested
        if ($generateCoverage) {
            // Ensure the coverage directory exists
            $coverageDir = getcwd() . '/reports/coverage';
            if (!is_dir($coverageDir)) {
                mkdir($coverageDir, 0755, true);
            }
            
            // Add coverage options
            $command[] = '--coverage-html reports/coverage';
        }
        
        // Show command
        $commandStr = implode(' ', $command);
        $this->info("Running: {$commandStr}");
        
        // Execute PHPUnit
        $exitCode = 0;
        $this->line('');
        $this->line('PHPUnit Test Results:');
        $this->line('=====================');
        passthru($commandStr, $exitCode);
        
        // PHPUnit returns 0 for success, 1 for failures, 2 for errors, and 3 for warnings
        // We'll consider warnings as success for our purposes
        $success = ($exitCode === 0 || $exitCode === 3);
        
        if ($success) {
            $this->line('');
            $this->info('All tests passed!');
            
            if ($generateCoverage) {
                $this->info('Coverage report generated at: reports/coverage/index.html');
            }
            
            return 0;
        } else {
            $this->line('');
            $this->error('Tests failed with exit code ' . $exitCode);
            return 1;
        }
    }
}
