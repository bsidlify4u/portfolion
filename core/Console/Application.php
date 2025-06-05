<?php

namespace Portfolion\Console;

use Portfolion\Config;
use Portfolion\Console\Commands\MakeControllerCommand;
use Portfolion\Console\Commands\MakeModelCommand;
use Portfolion\Console\Commands\MigrationCommand;
use Portfolion\Console\Commands\DiagnosticCommand;
use Portfolion\Console\Commands\ServeCommand;
use Portfolion\Console\Commands\EnvSetupCommand;
use Portfolion\Console\Commands\EnvSwitchCommand;
use Portfolion\Console\Commands\TestSetupCommand;
use Portfolion\Console\Commands\TestCommand;
use Portfolion\Console\Commands\TestMigrateCommand;
use Portfolion\Console\Commands\CacheTestCommand;
use Portfolion\Console\Commands\QueueWorkCommand;
use Portfolion\Console\Commands\QueueMigrateCommand;
use Portfolion\Console\Commands\QueueDispatchCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\ConsoleOutput;

class Application
{
    /**
     * The application version
     */
    const VERSION = '1.0.0';
    
    /**
     * Available commands
     *
     * @var array
     */
    protected array $commands = [];
    
    /**
     * Create a new console application
     */
    public function __construct()
    {
        // Initialize config
        $config = Config::getInstance();
        
        // Register built-in commands
        $this->registerCommands();
    }
    
    /**
     * Register all available commands
     */
    protected function registerCommands(): void
    {
        $this->commands = [
            'make:controller' => new MakeControllerCommand(),
            'make:model' => new MakeModelCommand(),
            'migrate' => new MigrationCommand(),
            'diagnostic' => new DiagnosticCommand(),
            'serve' => new ServeCommand(),
            'env:setup' => new EnvSetupCommand(),
            'env:switch' => new EnvSwitchCommand(),
            'test:setup' => new TestSetupCommand(),
            'test:migrate' => new TestMigrateCommand(),
            'test' => new TestCommand(),
            // Cache commands
            'cache:test' => new CacheTestCommand(),
            // Queue commands
            'queue:work' => new QueueWorkCommand(),
            'queue:migrate' => new QueueMigrateCommand(),
            'queue:dispatch' => new QueueDispatchCommand()
        ];
    }
    
    /**
     * Run the console application
     */
    public function run(): void
    {
        global $argv;
        
        // Show help if no command provided
        if (!isset($argv[1])) {
            $this->showHelp();
            return;
        }
        
        $command = $argv[1];
        $args = array_slice($argv, 2);
        
        // Handle help
        if ($command === 'help' || $command === '--help' || $command === '-h') {
            $this->showHelp();
            return;
        }
        
        // Handle version
        if ($command === '--version' || $command === '-v') {
            $this->showVersion();
            return;
        }
        
        // Handle command
        if (isset($this->commands[$command])) {
            $cmdObj = $this->commands[$command];
            
            try {
                // Simple argument parsing for now
                $parameters = [];
                
                foreach ($args as $arg) {
                    // Handle --option=value format
                    if (strpos($arg, '--') === 0 && strpos($arg, '=') !== false) {
                        list($optName, $optValue) = explode('=', substr($arg, 2), 2);
                        $parameters["--{$optName}"] = $optValue;
                    }
                    // Handle --option format (flags)
                    elseif (strpos($arg, '--') === 0) {
                        $optName = substr($arg, 2);
                        $parameters["--{$optName}"] = true;
                    }
                    // Handle -o format (short options)
                    elseif (strpos($arg, '-') === 0 && strlen($arg) === 2) {
                        $optName = substr($arg, 1);
                        $parameters["--{$optName}"] = true;
                    }
                }
                
                $input = new \Symfony\Component\Console\Input\ArrayInput($parameters);
                $output = new ConsoleOutput();
                
                // Run the command
                $cmdObj->run($input, $output);
            } catch (\Exception $e) {
                $output = new ConsoleOutput();
                $output->writeln("<e>Error: {$e->getMessage()}</e>");
                exit(1);
            }
        } else {
            echo "Error: Command '{$command}' not found.\n";
            $this->showHelp();
        }
    }
    
    /**
     * Show help information
     */
    protected function showHelp(): void
    {
        echo "Portfolion Framework " . self::VERSION . "\n\n";
        echo "Usage:\n";
        echo "  php portfolion [command] [options]\n\n";
        echo "Available commands:\n";
        
        foreach ($this->commands as $name => $command) {
            echo "  {$name}" . str_repeat(' ', 20 - strlen($name)) . $command->getDescription() . "\n";
        }
        
        echo "\nOptions:\n";
        echo "  -h, --help     Display this help message\n";
        echo "  -v, --version  Display version information\n";
    }
    
    /**
     * Show version information
     */
    protected function showVersion(): void
    {
        echo "Portfolion Framework " . self::VERSION . "\n";
    }
} 