<?php
namespace Portfolion\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Style\SymfonyStyle;

class EnvSwitchCommand extends BaseCommand
{
    /**
     * The name of the console command.
     */
    protected static $defaultName = 'env:switch';

    /**
     * The console command description.
     */
    protected static $defaultDescription = 'Switch between environment configurations';

    /**
     * Configure the command options.
     */
    protected function configure(): void
    {
        $this
            ->addArgument('environment', InputArgument::REQUIRED, 'The environment to switch to (development, production, testing)');
    }

    /**
     * Execute the console command.
     */
    protected function handle(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $environment = $input->getArgument('environment');
        $validEnvironments = ['development', 'production', 'testing'];
        
        if (!in_array($environment, $validEnvironments)) {
            $io->error("Invalid environment: {$environment}. Valid options are: " . implode(', ', $validEnvironments));
            return Command::FAILURE;
        }
        
        $io->title("Switching to {$environment} Environment");
        
        $envPath = base_path('.env');
        
        if (!file_exists($envPath)) {
            $io->error('No .env file found. Please run env:setup first.');
            return Command::FAILURE;
        }
        
        // Update the APP_ENV value
        $this->updateEnvValue($envPath, 'APP_ENV', $environment);
        
        // Update APP_DEBUG based on environment
        $debug = ($environment === 'production') ? 'false' : 'true';
        $this->updateEnvValue($envPath, 'APP_DEBUG', $debug);
        
        $io->success("Environment switched to {$environment}");
        
        // Additional environment-specific configurations
        switch ($environment) {
            case 'production':
                $this->updateEnvValue($envPath, 'LOG_LEVEL', 'error');
                $this->updateEnvValue($envPath, 'OPCACHE_ENABLE', 'true');
                $this->updateEnvValue($envPath, 'QUERY_CACHE', 'true');
                $io->info('Production optimizations enabled.');
                break;
                
            case 'development':
                $this->updateEnvValue($envPath, 'LOG_LEVEL', 'debug');
                $this->updateEnvValue($envPath, 'OPCACHE_ENABLE', 'false');
                $this->updateEnvValue($envPath, 'QUERY_CACHE', 'false');
                $io->info('Development mode enabled with detailed logging.');
                break;
                
            case 'testing':
                $this->updateEnvValue($envPath, 'LOG_LEVEL', 'debug');
                $this->updateEnvValue($envPath, 'DB_CONNECTION', 'sqlite');
                $this->updateEnvValue($envPath, 'DB_DATABASE', ':memory:');
                $io->info('Testing mode enabled with in-memory database.');
                break;
        }
        
        // Clear configuration cache
        $cachePath = base_path('storage/framework/cache/config.cache.php');
        if (file_exists($cachePath)) {
            unlink($cachePath);
            $io->info('Configuration cache cleared.');
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Update a value in the .env file.
     */
    protected function updateEnvValue(string $envPath, string $key, string $value): void
    {
        $content = file_get_contents($envPath);
        
        // If the key exists, replace its value
        if (preg_match("/^{$key}=/m", $content)) {
            $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
        } else {
            // Otherwise, add the key-value pair at the end
            $content .= PHP_EOL . "{$key}={$value}";
        }
        
        file_put_contents($envPath, $content);
    }
} 