<?php
namespace Portfolion\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'analyze',
    description: 'Analyze the application code for potential issues'
)]
class AnalyzeCommand extends Command {
    protected function execute(InputInterface $input, OutputInterface $output): int {
        $output->writeln('<info>Analyzing application code...</info>');
        
        // Run static analysis with PHPStan
        $phpstan = './vendor/bin/phpstan';
        if (file_exists($phpstan)) {
            passthru($phpstan . ' analyze --level=5 app core', $result);
            if ($result !== 0) {
                $output->writeln('<error>Static analysis found issues.</error>');
                return Command::FAILURE;
            }
        }
        
        $output->writeln('<info>✓ Code analysis completed successfully.</info>');
        return Command::SUCCESS;
    }
}
