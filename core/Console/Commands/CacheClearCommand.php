<?php

namespace Portfolion\Console\Commands;

use Portfolion\Console\Command;
use Portfolion\Cache\Cache;

class CacheClearCommand extends Command
{
    /**
     * Command name
     */
    protected string $name = 'cache:clear';
    
    /**
     * Command description
     */
    protected string $description = 'Clear the application cache';
    
    /**
     * Execute the command
     *
     * @param array $args Command arguments
     * @return int 0 if everything went fine, or an exit code
     */
    public function execute(array $args): int
    {
        try {
            // Get cache instance
            $cache = Cache::getInstance();
            
            // Check if specific cache to clear
            $store = null;
            foreach ($args as $arg) {
                if (strpos($arg, '--store=') === 0) {
                    $store = substr($arg, 8);
                    break;
                }
            }
            
            if ($store) {
                // Clear specific cache store
                $this->line("Clearing cache store: {$store}");
                $cache->store($store)->flush();
            } else {
                // Clear default cache
                $this->line("Clearing all caches...");
                $cache->flush();
                
                // Clear config cache if exists
                if (file_exists('storage/cache/config.php')) {
                    unlink('storage/cache/config.php');
                    $this->line("Configuration cache cleared!");
                }
                
                // Clear route cache if exists
                if (file_exists('storage/cache/routes.php')) {
                    unlink('storage/cache/routes.php');
                    $this->line("Route cache cleared!");
                }
                
                // Clear view cache
                $this->clearDirectory('storage/cache/views');
                $this->line("View cache cleared!");
            }
            
            $this->info("Cache cleared successfully!");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to clear cache: " . $e->getMessage());
            return 1;
        }
    }
    
    /**
     * Clear a directory's contents
     *
     * @param string $directory
     * @return void
     */
    protected function clearDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        
        $files = scandir($directory);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $path = $directory . '/' . $file;
            
            if (is_dir($path)) {
                $this->clearDirectory($path);
                rmdir($path);
            } else {
                unlink($path);
            }
        }
    }
} 