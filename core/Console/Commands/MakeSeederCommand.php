<?php

namespace Portfolion\Console\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MakeSeederCommand extends MakeCommand
{
    /**
     * The name of the command.
     */
    protected static $defaultName = 'make:seeder';

    /**
     * The description of the command.
     */
    protected static $defaultDescription = 'Create a new database seeder';

    /**
     * The type of class being generated.
     */
    protected string $type = 'Seeder';
    
    /**
     * The console input instance.
     */
    protected InputInterface $input;
    
    /**
     * Execute the console command.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        return parent::execute($input, $output);
    }

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        return __DIR__ . '/stubs/seeder.stub';
    }
    
    /**
     * Get the base path for the class.
     */
    protected function getBasePath(): string
    {
        return database_path('seeders');
    }
    
    /**
     * Get the default namespace for the class.
     */
    protected function getNamespace(string $name): string
    {
        return 'Database\\Seeders';
    }
} 