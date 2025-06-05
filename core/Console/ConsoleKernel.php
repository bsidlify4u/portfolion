<?php
namespace Portfolion\Console;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleKernel {
    private Application $app;
    
    protected array $commands = [
        \Portfolion\Console\Commands\TestCommand::class,
        \Portfolion\Console\Commands\AnalyzeCommand::class,
        \Portfolion\Console\Commands\DiagnosticCommand::class,
        \Portfolion\Console\Commands\ServeCommand::class,
        \Portfolion\Console\Commands\SetupCommand::class,
        \Portfolion\Console\Commands\GenerateConfigKey::class,
        
        // Generator commands
        \Portfolion\Console\Commands\MakeModelCommand::class,
        \Portfolion\Console\Commands\MakeControllerCommand::class,
        \Portfolion\Console\Commands\MakeMigrationCommand::class,
        \Portfolion\Console\Commands\MakeSeederCommand::class,
        \Portfolion\Console\Commands\MakeCommandCommand::class,
        \Portfolion\Console\Commands\MakeMiddlewareCommand::class,
        \Portfolion\Console\Commands\MakeProviderCommand::class,
        
        // Database commands
        \Portfolion\Console\Commands\MigrateCommand::class,
        \Portfolion\Console\Commands\MigrateResetCommand::class,
        
        // Cache commands
        \Portfolion\Console\Commands\CacheTestCommand::class,
        
        // Queue commands
        \Portfolion\Console\Commands\QueueWorkCommand::class,
        \Portfolion\Console\Commands\QueueDispatchCommand::class,
        \Portfolion\Console\Commands\QueueMigrateCommand::class,
    ];

    public function __construct() {
        $this->app = new Application('Portfolion Framework', '1.0.0');
        $this->app->setCatchExceptions(true);
        $this->app->setAutoExit(false);
        $this->registerCommands();
        $this->registerErrorHandler();
    }
    
    protected function registerCommands(): void {
        foreach ($this->commands as $command) {
            if (class_exists($command)) {
                try {
                    $this->add(new $command());
                } catch (\Throwable $e) {
                    error_log("Failed to register command {$command}: " . $e->getMessage());
                }
            }
        }
    }

    protected function registerErrorHandler(): void {
        set_error_handler(function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        $this->app->setAutoExit(false);
    }
    
    public function add($command): void {
        $this->app->add($command);
    }
    
    public function run(?InputInterface $input = null, ?OutputInterface $output = null): int {
        try {
            if (!$output) {
                $output = new ConsoleOutput();
            }
            
            return $this->app->run($input ?? new ArgvInput(), $output);
        } catch (\Throwable $e) {
            if (!$output) {
                $output = new ConsoleOutput();
            }
            $output->writeln("<e>Error: " . $e->getMessage() . "</e>");
            $output->writeln("<e>Stack trace:</e>");
            $output->writeln($e->getTraceAsString());
            return 1;
        }
    }
}
