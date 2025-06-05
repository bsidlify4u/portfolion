<?php
namespace Portfolion\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;

class EnvSetupCommand extends BaseCommand
{
    /**
     * The name of the console command.
     */
    protected static $defaultName = 'env:setup';

    /**
     * The console command description.
     */
    protected static $defaultDescription = 'Set up the environment configuration';

    /**
     * Configure the command options.
     */
    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force overwrite of existing .env file');
    }

    /**
     * Execute the console command.
     */
    protected function handle(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Setting Up Environment Configuration');
        
        $envPath = base_path('.env');
        $envExamplePath = base_path('config/.env.example');
        
        // Check if .env file already exists
        if (file_exists($envPath) && !$input->getOption('force')) {
            $io->warning('.env file already exists. Use --force to overwrite.');
            return Command::SUCCESS;
        }
        
        // Create example .env file if it doesn't exist
        if (!file_exists($envExamplePath)) {
            $this->createEnvExample($envExamplePath);
            $io->info('Created .env.example file in config directory.');
        }
        
        // Copy example file to .env
        if (copy($envExamplePath, $envPath)) {
            $io->success('.env file created successfully.');
            
            // Generate app key
            $key = $this->generateRandomKey();
            $this->updateEnvValue($envPath, 'APP_KEY', $key);
            $io->info('Application key set successfully.');
            
            return Command::SUCCESS;
        } else {
            $io->error('Failed to create .env file.');
            return Command::FAILURE;
        }
    }
    
    /**
     * Create the example .env file.
     */
    protected function createEnvExample(string $path): void
    {
        $content = <<<'EOT'
# Application Settings
APP_NAME=Portfolion
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_KEY=

# Database Settings
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=portfolion
DB_USERNAME=root
DB_PASSWORD=

# Cache Settings
CACHE_DRIVER=file
SESSION_DRIVER=file
SESSION_LIFETIME=120

# Mail Settings
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=null
MAIL_FROM_NAME="${APP_NAME}"

# Security Settings
ENCRYPTION_KEY=

# Logging Settings
LOG_LEVEL=debug

# Performance Settings
OPCACHE_ENABLE=false
QUERY_CACHE=false
EOT;
        
        file_put_contents($path, $content);
    }
    
    /**
     * Generate a random application key.
     */
    protected function generateRandomKey(): string
    {
        return 'base64:' . base64_encode(random_bytes(32));
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