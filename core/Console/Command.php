<?php

namespace Portfolion\Console;

abstract class Command
{
    /**
     * Command name
     */
    protected string $name;
    
    /**
     * Command description
     */
    protected string $description = 'A Portfolion command';
    
    /**
     * Execute the command
     *
     * @param array $args Command arguments
     * @return int 0 if everything went fine, or an exit code
     */
    abstract public function execute(array $args): int;
    
    /**
     * Get the command description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }
    
    /**
     * Get the command name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * Write an info message to the console
     *
     * @param string $message
     */
    protected function info(string $message): void
    {
        echo "\033[32m{$message}\033[0m\n";
    }
    
    /**
     * Write an error message to the console
     *
     * @param string $message
     */
    protected function error(string $message): void
    {
        echo "\033[31m{$message}\033[0m\n";
    }
    
    /**
     * Write a warning message to the console
     *
     * @param string $message
     */
    protected function warning(string $message): void
    {
        echo "\033[33m{$message}\033[0m\n";
    }
    
    /**
     * Write a line to the console
     *
     * @param string $message
     */
    protected function line(string $message): void
    {
        echo "{$message}\n";
    }
} 