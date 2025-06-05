<?php

namespace Portfolion\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Defuse\Crypto\Key;

class GenerateConfigKey extends Command {
    protected static $defaultName = 'config:key';
    protected static $defaultDescription = 'Generate a new encryption key for sensitive configuration values';

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $key = Key::createNewRandomKey();
        $keyString = $key->saveToAsciiSafeString();
        
        $keyPath = dirname(dirname(dirname(__DIR__))) . '/storage/app/config.key';
        $keyDir = dirname($keyPath);
        
        if (!is_dir($keyDir)) {
            mkdir($keyDir, 0755, true);
        }
        
        if (file_put_contents($keyPath, $keyString) === false) {
            $output->writeln('<error>Failed to save encryption key</error>');
            return Command::FAILURE;
        }
        
        chmod($keyPath, 0600);
        
        $output->writeln('<info>Configuration encryption key generated successfully!</info>');
        return Command::SUCCESS;
    }
}
