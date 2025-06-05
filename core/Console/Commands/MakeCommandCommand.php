<?php

namespace Portfolion\Console\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MakeCommandCommand extends MakeCommand
{
    /**
     * The name of the command.
     */
    protected static $defaultName = 'make:command';

    /**
     * The description of the command.
     */
    protected static $defaultDescription = 'Create a new console command';

    /**
     * The type of class being generated.
     */
    protected string $type = 'Command';
    
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
     * Configure the command options.
     */
    protected function configure(): void
    {
        parent::configure();
        
        $this->addOption('command', 'c', InputOption::VALUE_OPTIONAL, 'The terminal command that should be assigned', 'command:name');
    }

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        return __DIR__ . '/stubs/command.stub';
    }
    
    /**
     * Get the base path for the class.
     */
    protected function getBasePath(): string
    {
        return base_path('app/Console/Commands');
    }
    
    /**
     * Get the default namespace for the class.
     */
    protected function getNamespace(string $name): string
    {
        return 'App\\Console\\Commands';
    }
    
    /**
     * Build the class with the given name.
     */
    protected function buildClass(string $name): string
    {
        $stub = parent::buildClass($name);
        
        // Replace the command name
        $commandName = $this->input->getOption('command');
        
        return str_replace('{{ command }}', $commandName, $stub);
    }
} 