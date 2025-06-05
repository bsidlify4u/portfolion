<?php

namespace Portfolion\Console\Commands;

use Portfolion\Database\Migrations\MigrationCreator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MakeMigrationCommand extends BaseCommand
{
    /**
     * The name of the console command.
     */
    protected static $defaultName = 'make:migration';

    /**
     * The console command description.
     */
    protected static $defaultDescription = 'Create a new migration file';

    /**
     * Configure the command options.
     */
    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the migration')
            ->addOption('create', null, InputOption::VALUE_OPTIONAL, 'The table to be created')
            ->addOption('table', null, InputOption::VALUE_OPTIONAL, 'The table to be updated')
            ->addOption('path', null, InputOption::VALUE_OPTIONAL, 'The location where the migration should be created');
    }

    /**
     * Execute the console command.
     */
    protected function handle(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $name = $input->getArgument('name');
        $table = $input->getOption('table') ?: $input->getOption('create');
        $create = $input->getOption('create') !== false;
        $path = $input->getOption('path');
        
        // Create the migration
        $creator = new MigrationCreator($path);
        
        try {
            $file = $creator->create($name, $table, $create);
            
            $io->success("Migration created successfully: " . basename($file));
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            
            return Command::FAILURE;
        }
    }
} 