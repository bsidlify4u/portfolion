<?php
namespace Portfolion\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class ServeCommand extends Command
{
    /**
     * The name of the console command.
     */
    protected static $defaultName = 'serve';

    /**
     * The console command description.
     */
    protected static $defaultDescription = 'Start the PHP development server';

    /**
     * Configure the command options.
     */
    protected function configure(): void
    {
        $this
            ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'The host address to serve the application on', '127.0.0.1')
            ->addOption('port', 'p', InputOption::VALUE_OPTIONAL, 'The port to serve the application on', 8000)
            ->addOption('docroot', null, InputOption::VALUE_OPTIONAL, 'The document root', 'public')
            ->addOption('no-interaction', 'n', InputOption::VALUE_NONE, 'Run without interactive output')
            ->addOption('watch', 'w', InputOption::VALUE_NONE, 'Watch for file changes and reload the server');
    }

    /**
     * Execute the console command.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $host = $input->getOption('host');
        $port = $input->getOption('port');
        $documentRoot = $this->getDocumentRoot($input->getOption('docroot'));
        
        // Create public directory if it doesn't exist
        if (!is_dir($documentRoot)) {
            mkdir($documentRoot, 0755, true);
        }
        
        // Make sure router.php exists
        $this->ensureRouterFileExists();
        
        $io->title('Starting Portfolion Development Server...');
        $io->text([
            "Server running at: <info>http://{$host}:{$port}</info>",
            "Document Root: <info>{$documentRoot}</info>",
            "Press Ctrl+C to stop the server",
        ]);
        
        // Use direct approach without router script
        $serverCommand = $this->findPhpBinary() . " -S {$host}:{$port} -t \"{$documentRoot}\"";
        
        if ($input->getOption('watch')) {
            return $this->runWithWatcher($serverCommand, $io, $input, $output);
        }
        
        return $this->runServer($serverCommand, $io);
    }
    
    /**
     * Get the document root path.
     */
    protected function getDocumentRoot(string $path): string
    {
        // Use getcwd() to get the current working directory and build absolute path
        $basePath = getcwd();
        return $basePath . DIRECTORY_SEPARATOR . $path;
    }
    
    /**
     * Get the router file path.
     */
    protected function getRouterFile(): string
    {
        // Use getcwd() to get the current working directory and build absolute path
        $basePath = getcwd();
        return $basePath . DIRECTORY_SEPARATOR . 'server.php';
    }
    
    /**
     * Run the server and watch for file changes.
     */
    protected function runWithWatcher(string $serverCommand, SymfonyStyle $io, InputInterface $input, OutputInterface $output): int
    {
        $io->note('Watching for file changes...');
        
        // Check if node is installed
        if (!$this->commandExists('node')) {
            $io->error('Node.js is required for the file watching feature.');
            return Command::FAILURE;
        }
        
        // Check if nodemon is installed
        if (!$this->commandExists('nodemon')) {
            $io->note('Installing nodemon...');
            
            $process = new Process(['npm', 'install', '-g', 'nodemon']);
            $process->run();
            
            if (!$process->isSuccessful()) {
                $io->error('Failed to install nodemon. Please install it manually with: npm install -g nodemon');
                return Command::FAILURE;
            }
        }
        
        // Start the server with nodemon
        $process = new Process([
            'nodemon',
            '--exec', $serverCommand,
            '--ext', 'php,html,css,js,json',
            '--ignore', 'vendor/',
            '--ignore', 'node_modules/',
            '--ignore', 'storage/',
        ], getcwd());
        
        $process->setTty(true);
        $process->setTimeout(null);
        $process->start();
        
        foreach ($process as $type => $data) {
            $output->write($data);
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Run the server.
     */
    protected function runServer(string $serverCommand, SymfonyStyle $io): int
    {
        $process = Process::fromShellCommandline($serverCommand, getcwd(), null, null, null);
        $process->setTty(true);
        $process->setTimeout(null);
        
        try {
            $process->run(function ($type, $buffer) {
                echo $buffer;
            });
            
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Ensure the router file exists.
     */
    protected function ensureRouterFileExists(): void
    {
        $routerFile = getcwd() . DIRECTORY_SEPARATOR . 'server.php';
        
        if (!file_exists($routerFile)) {
            $content = <<<'EOF'
<?php

/**
 * Portfolion - Development Server Router
 *
 * This file is used by the PHP development server to route all requests
 * to the application's front controller. This allows the development server
 * to mimic the behavior of a production server with URL rewriting.
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// This file allows us to emulate Apache's "mod_rewrite" functionality
// from the built-in PHP web server.
if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}

require_once __DIR__.'/public/index.php';
EOF;
            
            file_put_contents($routerFile, $content);
        }
    }
    
    /**
     * Get the path to the PHP binary.
     */
    protected function findPhpBinary(): string
    {
        $phpBinary = PHP_BINARY;
        
        // Check if using PHP-FPM/CGI
        if (strpos(PHP_SAPI, 'cgi') !== false) {
            $phpBinary = 'php';
        }
        
        return $phpBinary;
    }
    
    /**
     * Check if a command exists on the system.
     */
    protected function commandExists(string $command): bool
    {
        $os = PHP_OS;
        
        if (strtoupper(substr($os, 0, 3)) === 'WIN') {
            $whereCommand = 'where';
        } else {
            $whereCommand = 'which';
        }
        
        $process = new Process([$whereCommand, $command]);
        $process->run();
        
        return $process->isSuccessful();
    }
}
