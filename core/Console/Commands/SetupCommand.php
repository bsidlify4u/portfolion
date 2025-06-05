<?php
namespace Portfolion\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'setup',
    description: 'Set up the application structure and permissions'
)]
class SetupCommand extends BaseCommand 
{
    protected function handle(InputInterface $input, OutputInterface $output): int {
        $this->info($output, 'Setting up application structure...');
        
        $directories = [
            storage_path('cache'),
            storage_path('logs'),
            storage_path('framework'),
            storage_path('framework/views'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('uploads'),
            base_path('bootstrap'),
            base_path('database/migrations'),
            base_path('database/seeds'),
            base_path('database/factories'),
            base_path('resources/views'),
            base_path('app/Controllers'),
            base_path('app/Models'),
            base_path('app/Views'),
        ];
        
        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                if (mkdir($dir, 0755, true)) {
                    $this->info($output, "Created directory: {$dir}");
                } else {
                    $this->error($output, "Failed to create directory: {$dir}");
                    return Command::FAILURE;
                }
            }
            
            if (!is_writable($dir)) {
                if (chmod($dir, 0755)) {
                    $this->info($output, "Set permissions for: {$dir}");
                } else {
                    $this->error($output, "Failed to set permissions for: {$dir}");
                    return Command::FAILURE;
                }
            }
        }

        // Create .env if it doesn't exist
        if (!file_exists(base_path('.env'))) {
            if (file_exists(base_path('.env.example'))) {
                if (copy(base_path('.env.example'), base_path('.env'))) {
                    $this->info($output, 'Created .env file from .env.example');
                } else {
                    $this->error($output, 'Failed to create .env file');
                    return Command::FAILURE;
                }
            } else {
                $envContent = "APP_NAME=Portfolion\n" .
                             "APP_ENV=local\n" .
                             "APP_DEBUG=true\n" .
                             "APP_URL=http://localhost:8000\n\n" .
                             "DB_CONNECTION=mysql\n" .
                             "DB_HOST=127.0.0.1\n" .
                             "DB_PORT=3306\n" .
                             "DB_DATABASE=portfolion\n" .
                             "DB_USERNAME=portfolion\n" .
                             "DB_PASSWORD=portfolion\n\n" .
                             "CACHE_DRIVER=file\n" .
                             "SESSION_DRIVER=file\n" .
                             "QUEUE_DRIVER=sync\n";
                
                if (file_put_contents(base_path('.env'), $envContent)) {
                    $this->info($output, 'Created default .env file');
                } else {
                    $this->error($output, 'Failed to create .env file');
                    return Command::FAILURE;
                }
            }
        }

        $this->success($output, 'Application structure setup completed successfully!');
        return Command::SUCCESS;
    }
}
