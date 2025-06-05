<?php

namespace Portfolion\Console\Commands;

use Portfolion\Console\Command;
use Portfolion\Console\InputInterface;
use Portfolion\Console\OutputInterface;
use Portfolion\Session\Session;
use Portfolion\Session\SessionManager;

#[AsCommand(
    name: 'session:test',
    description: 'Test session functionality'
)]
class SessionTestCommand extends BaseCommand
{
    protected function handle(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("<i>Testing session functionality...</i>");
        
        try {
            // Initialize session manager
            $sessionManager = new SessionManager();
            $session = Session::getInstance();
            
            // Test session start
            $output->writeln("Starting session...");
            $result = $sessionManager->start();
            $output->writeln("Session started: " . ($result ? 'true' : 'false'));
            $output->writeln("Session ID: " . $session->getId());
            
            // Test session write
            $output->writeln("\nTesting session write...");
            $session->set('test_key', 'test_value');
            $session->set('test_array', ['foo' => 'bar', 'baz' => 'qux']);
            $output->writeln("Session data written.");
            
            // Test session read
            $output->writeln("\nTesting session read...");
            $value = $session->get('test_key');
            $output->writeln("test_key = " . $value);
            $array = $session->get('test_array');
            $output->writeln("test_array = " . json_encode($array));
            
            // Test session has
            $output->writeln("\nTesting session has...");
            $output->writeln("Has test_key: " . ($session->has('test_key') ? 'true' : 'false'));
            $output->writeln("Has nonexistent_key: " . ($session->has('nonexistent_key') ? 'true' : 'false'));
            
            // Test session flash
            $output->writeln("\nTesting session flash...");
            $session->flash('flash_key', 'flash_value');
            $output->writeln("flash_key = " . $session->getFlash('flash_key'));
            $output->writeln("Has flash_key: " . ($session->hasFlash('flash_key') ? 'true' : 'false'));
            
            // Test session regenerate
            $output->writeln("\nTesting session regenerate...");
            $oldId = $session->getId();
            $session->regenerateId();
            $newId = $session->getId();
            $output->writeln("Old ID: " . $oldId);
            $output->writeln("New ID: " . $newId);
            $output->writeln("IDs different: " . ($oldId !== $newId ? 'true' : 'false'));
            
            // Test session destroy
            $output->writeln("\nTesting session destroy...");
            $result = $session->destroy();
            $output->writeln("Session destroyed: " . ($result ? 'true' : 'false'));
            
            $this->success($output, "Session tests completed successfully!");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error($output, "Session test failed: " . $e->getMessage());
            $output->writeln("<e>Stack trace:</e>");
            $output->writeln($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
} 