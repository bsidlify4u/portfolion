<?php

namespace Portfolion\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class MakeCommand extends Command
{
    /**
     * The filesystem instance.
     */
    protected $filesystem;

    /**
     * The type of class being generated.
     */
    protected string $type;

    /**
     * The stub path for the generator.
     */
    protected string $stubPath;

    /**
     * Execute the console command.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $name = $input->getArgument('name');
        
        $this->createDirectory(dirname($this->getPath($name)));
        
        $path = $this->getPath($name);
        
        if (file_exists($path) && !$input->getOption('force')) {
            $io->error("File already exists: {$path}");
            return Command::FAILURE;
        }
        
        file_put_contents($path, $this->buildClass($name));
        
        $relativePath = str_replace(base_path() . '/', '', $path);
        $io->success("Created: {$relativePath}");
        
        return Command::SUCCESS;
    }
    
    /**
     * Configure the command.
     */
    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the class')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite existing files');
    }
    
    /**
     * Get the destination class path.
     */
    protected function getPath(string $name): string
    {
        $name = str_replace('\\', '/', $name);
        
        return $this->getBasePath() . '/' . $name . '.php';
    }
    
    /**
     * Get the base path for the class.
     */
    abstract protected function getBasePath(): string;
    
    /**
     * Build the class with the given name.
     */
    protected function buildClass(string $name): string
    {
        $stub = file_get_contents($this->getStub());
        
        return $this->replaceNamespace($stub, $name)
                    ->replaceClass($stub, $name);
    }
    
    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        return $this->stubPath ?: __DIR__ . '/stubs/' . strtolower($this->type) . '.stub';
    }
    
    /**
     * Replace the namespace for the given stub.
     */
    protected function replaceNamespace(string &$stub, string $name): self
    {
        $stub = str_replace(
            ['DummyNamespace', '{{ namespace }}', '{{namespace}}'],
            $this->getNamespace($name),
            $stub
        );
        
        return $this;
    }
    
    /**
     * Get the class namespace.
     */
    protected function getNamespace(string $name): string
    {
        return trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
    }
    
    /**
     * Replace the class name for the given stub.
     */
    protected function replaceClass(string &$stub, string $name): string
    {
        $class = str_replace($this->getNamespace($name) . '\\', '', $name);
        
        $stub = str_replace(
            ['DummyClass', '{{ class }}', '{{class}}'],
            $class,
            $stub
        );
        
        return $stub;
    }
    
    /**
     * Create a directory if it doesn't exist.
     */
    protected function createDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
} 