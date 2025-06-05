<?php
namespace Portfolion\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Portfolion\View\AssetCompiler;

class AssetCompileCommand extends BaseCommand
{
    /**
     * The name of the console command.
     */
    protected static $defaultName = 'assets:compile';

    /**
     * The console command description.
     */
    protected static $defaultDescription = 'Compile and bundle assets';
    
    /**
     * Define command options
     */
    protected $options = [
        'watch' => 'Watch files for changes and recompile automatically',
        'production' => 'Compile assets for production (with minification)',
        'env' => 'The environment to compile assets for'
    ];

    /**
     * Execute the console command.
     */
    protected function handle(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        // Set environment variables based on options
        if ($input->getOption('production')) {
            putenv('APP_ENV=production');
        }
        
        if ($env = $input->getOption('env')) {
            putenv("APP_ENV={$env}");
        }
        
        $io->title('Compiling Assets');
        
        // Create the compiler
        $compiler = new AssetCompiler();
        
        if ($input->getOption('watch')) {
            return $this->watchAssets($compiler, $io);
        } else {
            return $this->compileAssets($compiler, $io);
        }
    }
    
    /**
     * Compile assets once
     * 
     * @param AssetCompiler $compiler
     * @param SymfonyStyle $io
     * @return int
     */
    protected function compileAssets(AssetCompiler $compiler, SymfonyStyle $io): int
    {
        $io->section('Compiling assets...');
        
        try {
            $result = $compiler->compileAll();
            
            if ($result) {
                $io->success('Assets compiled successfully!');
                return Command::SUCCESS;
            } else {
                $io->warning('No assets were compiled. Check your configuration.');
                return Command::SUCCESS;
            }
        } catch (\Exception $e) {
            $io->error('Asset compilation failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Watch assets for changes and recompile
     * 
     * @param AssetCompiler $compiler
     * @param SymfonyStyle $io
     * @return int
     */
    protected function watchAssets(AssetCompiler $compiler, SymfonyStyle $io): int
    {
        $io->section('Watching assets for changes...');
        $io->info('Press Ctrl+C to stop watching');
        
        // Initial compilation
        try {
            $compiler->compileAll();
            $io->success('Initial compilation successful!');
        } catch (\Exception $e) {
            $io->error('Initial compilation failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
        
        // Get directories to watch
        $config = \Portfolion\Config::getInstance();
        $sassDir = $config->get('assets.compilation.sass.source_dir');
        $jsDir = $config->get('assets.compilation.js.source_dir');
        
        $watchDirs = array_filter([$sassDir, $jsDir], function($dir) {
            return !empty($dir) && is_dir($dir);
        });
        
        if (empty($watchDirs)) {
            $io->error('No valid directories to watch. Check your configuration.');
            return Command::FAILURE;
        }
        
        // Check if we have fswatch or inotifywait available
        $hasWatchTool = false;
        
        // Try fswatch first (macOS and some Linux)
        exec('which fswatch 2>/dev/null', $output, $returnCode);
        if ($returnCode === 0) {
            $hasWatchTool = true;
            $watchCommand = 'fswatch -o ' . implode(' ', $watchDirs);
            
            $io->info('Using fswatch to monitor for changes');
        } else {
            // Try inotifywait (Linux)
            exec('which inotifywait 2>/dev/null', $output, $returnCode);
            if ($returnCode === 0) {
                $hasWatchTool = true;
                $watchCommand = 'inotifywait -m -r -e modify,create,delete ' . implode(' ', $watchDirs);
                
                $io->info('Using inotifywait to monitor for changes');
            }
        }
        
        if (!$hasWatchTool) {
            $io->error('No file watching tool available. Please install fswatch or inotify-tools.');
            return Command::FAILURE;
        }
        
        // Start watching
        $descriptorspec = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w']   // stderr
        ];
        
        $process = proc_open($watchCommand, $descriptorspec, $pipes);
        
        if (is_resource($process)) {
            // Make stdout non-blocking
            stream_set_blocking($pipes[1], 0);
            
            $io->writeln('Watching for changes...');
            
            while (true) {
                $change = fgets($pipes[1]);
                if ($change) {
                    $io->writeln('');
                    $io->writeln('Change detected: ' . trim($change));
                    $io->writeln('Recompiling...');
                    
                    try {
                        $compiler->compileAll();
                        $io->success('Compilation successful!');
                    } catch (\Exception $e) {
                        $io->error('Compilation failed: ' . $e->getMessage());
                    }
                    
                    $io->writeln('Watching for changes...');
                }
                
                // Small delay to reduce CPU usage
                usleep(500000); // 0.5 seconds
            }
            
            // We'll never reach this point unless the watching is interrupted
            proc_close($process);
        }
        
        return Command::SUCCESS;
    }
} 