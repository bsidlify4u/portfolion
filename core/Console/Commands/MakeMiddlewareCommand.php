<?php

namespace Portfolion\Console\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeMiddlewareCommand extends MakeCommand
{
    /**
     * The name of the command.
     */
    protected static $defaultName = 'make:middleware';

    /**
     * The description of the command.
     */
    protected static $defaultDescription = 'Create a new middleware class';

    /**
     * The type of class being generated.
     */
    protected string $type = 'Middleware';
    
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
        return __DIR__ . '/stubs/middleware.stub';
    }
    
    /**
     * Get the base path for the class.
     */
    protected function getBasePath(): string
    {
        return base_path('app/Http/Middleware');
    }
    
    /**
     * Get the default namespace for the class.
     */
    protected function getNamespace(string $name): string
    {
        return 'App\\Http\\Middleware';
    }
} 