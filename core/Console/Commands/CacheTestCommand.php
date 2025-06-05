<?php

namespace Portfolion\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Portfolion\Cache\Cache;
use Portfolion\Cache\CacheManager;

#[AsCommand(
    name: 'cache:test',
    description: 'Test cache functionality'
)]
class CacheTestCommand extends BaseCommand
{
    protected function handle(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("<i>Testing cache functionality...</i>");
        
        try {
            // Get cache instance
            $cache = Cache::getInstance();
            
            // Test cache write
            $output->writeln("\nTesting cache write...");
            $result = $cache->put('test_key', 'test_value', 60);
            $output->writeln("Cache write result: " . ($result ? 'true' : 'false'));
            
            // Test cache read
            $output->writeln("\nTesting cache read...");
            $value = $cache->get('test_key');
            $output->writeln("test_key = " . $value);
            
            // Test cache has
            $output->writeln("\nTesting cache has...");
            $output->writeln("Has test_key: " . ($cache->has('test_key') ? 'true' : 'false'));
            $output->writeln("Has nonexistent_key: " . ($cache->has('nonexistent_key') ? 'true' : 'false'));
            
            // Test cache remember
            $output->writeln("\nTesting cache remember...");
            $value = $cache->remember('remember_key', 60, function () {
                return 'remembered_value';
            });
            $output->writeln("remember_key = " . $value);
            
            // Test cache forever
            $output->writeln("\nTesting cache forever...");
            $cache->forever('forever_key', 'forever_value');
            $output->writeln("forever_key = " . $cache->get('forever_key'));
            
            // Test cache increment/decrement
            $output->writeln("\nTesting cache increment/decrement...");
            $cache->put('counter', 5, 60);
            $output->writeln("counter (initial) = " . $cache->get('counter'));
            $cache->increment('counter', 3);
            $output->writeln("counter (after increment) = " . $cache->get('counter'));
            $cache->decrement('counter', 2);
            $output->writeln("counter (after decrement) = " . $cache->get('counter'));
            
            // Test cache forget
            $output->writeln("\nTesting cache forget...");
            $cache->forget('test_key');
            $output->writeln("Has test_key after forget: " . ($cache->has('test_key') ? 'true' : 'false'));
            
            // Test cache tags (if supported)
            $output->writeln("\nTesting cache tags...");
            try {
                $cache->tags(['tag1', 'tag2'])->put('tagged_key', 'tagged_value', 60);
                $value = $cache->tags(['tag1', 'tag2'])->get('tagged_key');
                $output->writeln("tagged_key = " . $value);
                $cache->tags(['tag1'])->flush();
                $output->writeln("Has tagged_key after tag flush: " . ($cache->tags(['tag1', 'tag2'])->has('tagged_key') ? 'true' : 'false'));
            } catch (\Exception $e) {
                $output->writeln("Cache tags not supported by the current driver: " . $e->getMessage());
            }
            
            // Test cache flush
            $output->writeln("\nTesting cache flush...");
            $cache->flush();
            $output->writeln("Has remember_key after flush: " . ($cache->has('remember_key') ? 'true' : 'false'));
            $output->writeln("Has forever_key after flush: " . ($cache->has('forever_key') ? 'true' : 'false'));
            
            $this->success($output, "Cache tests completed successfully!");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error($output, "Cache test failed: " . $e->getMessage());
            $output->writeln("<e>Stack trace:</e>");
            $output->writeln($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
} 