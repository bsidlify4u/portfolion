<?php
namespace Portfolion\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Attribute\AsCommand;
use Portfolion\Config;

#[AsCommand(
    name: 'diagnostic',
    description: 'Run system diagnostic checks'
)]
class DiagnosticCommand extends BaseCommand
{
    protected function handle(InputInterface $input, OutputInterface $output): int
    {
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
        
        $this->info($output, 'Running system diagnostics...');
        
        try {
            $output->writeln('Loading configuration...');
            $config = Config::getInstance();
            $output->writeln('Configuration loaded.');
            
            $output->writeln('Running checks...');
            
            $checks = [];
            
            // Run checks one by one with error handling
            try {
                $checks['PHP Version'] = $this->checkPhpVersion();
                $output->writeln('- PHP version check complete');
            } catch (\Throwable $e) {
                $output->writeln("<error>PHP version check failed: {$e->getMessage()}</error>");
                $checks['PHP Version'] = ['status' => false, 'message' => $e->getMessage()];
            }
            
            try {
                $checks['Required Extensions'] = $this->checkExtensions();
                $output->writeln('- Extensions check complete');
            } catch (\Throwable $e) {
                $output->writeln("<error>Extensions check failed: {$e->getMessage()}</error>");
                $checks['Required Extensions'] = ['status' => false, 'message' => $e->getMessage()];
            }
            
            try {
                $checks['Directory Permissions'] = $this->checkDirectoryPermissions();
                $output->writeln('- Directory permissions check complete');
            } catch (\Throwable $e) {
                $output->writeln("<error>Directory permissions check failed: {$e->getMessage()}</error>");
                $checks['Directory Permissions'] = ['status' => false, 'message' => $e->getMessage()];
            }
            
            try {
                $checks['Database Connection'] = $this->checkDatabase();
                $output->writeln('- Database connection check complete');
            } catch (\Throwable $e) {
                $output->writeln("<error>Database check failed: {$e->getMessage()}</error>");
                $checks['Database Connection'] = ['status' => false, 'message' => $e->getMessage()];
            }
            
            try {
                $checks['Cache Directory'] = $this->checkCache();
                $output->writeln('- Cache directory check complete');
            } catch (\Throwable $e) {
                $output->writeln("<error>Cache check failed: {$e->getMessage()}</error>");
                $checks['Cache Directory'] = ['status' => false, 'message' => $e->getMessage()];
            }
            
            try {
                $checks['Session Directory'] = $this->checkSessions();
                $output->writeln('- Session directory check complete');
            } catch (\Throwable $e) {
                $output->writeln("<error>Session check failed: {$e->getMessage()}</error>");
                $checks['Session Directory'] = ['status' => false, 'message' => $e->getMessage()];
            }
            
            try {
                $checks['Composer Dependencies'] = $this->checkComposerDependencies();
                $output->writeln('- Composer dependencies check complete');
            } catch (\Throwable $e) {
                $output->writeln("<error>Composer check failed: {$e->getMessage()}</error>");
                $checks['Composer Dependencies'] = ['status' => false, 'message' => $e->getMessage()];
            }
            
            $output->writeln('Building results table...');
            
            $table = new Table($output);
            $table->setHeaders(['Check', 'Status', 'Message']);
            
            $hasErrors = false;
            
            foreach ($checks as $name => $result) {
                $status = $result['status'] ? '✓' : '✗';
                $style = $result['status'] ? 'green' : 'red';
                
                if (!$result['status']) {
                    $hasErrors = true;
                }
                
                $table->addRow([
                    $name,
                    sprintf('<%s>%s</>', $style, $status),
                    $result['message']
                ]);
            }
            
            $table->render();
            
            if ($hasErrors) {
                $this->error($output, 'Some checks failed. Please fix the issues above.');
                return Command::FAILURE;
            }
            
            $this->success($output, 'All diagnostic checks passed!');
            return Command::SUCCESS;
            
        } catch (\Throwable $e) {
            $output->writeln("<error>Fatal error: " . $e->getMessage() . "</error>");
            $output->writeln("<error>Stack trace:</error>");
            $output->writeln($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
    
    private function checkPhpVersion(): array
    {
        $required = '7.4.0';
        $current = PHP_VERSION;
        
        return [
            'status' => version_compare($current, $required, '>='),
            'message' => "Current: {$current}, Required: >= {$required}"
        ];
    }
    
    private function checkExtensions(): array
    {
        $required = ['pdo', 'json', 'mbstring', 'openssl', 'xml'];
        $missing = [];
        
        foreach ($required as $ext) {
            if (!extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }
        
        return [
            'status' => empty($missing),
            'message' => empty($missing) 
                ? 'All required extensions are installed'
                : 'Missing extensions: ' . implode(', ', $missing)
        ];
    }
    
    private function checkDirectoryPermissions(): array
    {
        $directories = [
            storage_path('cache'),
            storage_path('logs'),
            storage_path('framework'),
            storage_path('framework/views'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
        ];
        
        $notWritable = [];
        
        foreach ($directories as $path) {
            if (!is_writable($path)) {
                $notWritable[] = str_replace(base_path() . '/', '', $path);
            }
        }
        
        return [
            'status' => empty($notWritable),
            'message' => empty($notWritable)
                ? 'All directories are writable'
                : 'Not writable: ' . implode(', ', $notWritable)
        ];
    }
    
    private function checkDatabase(): array
    {
        try {
            $config = Config::getInstance();
            error_log("Config instance created");
            
            $connection = $config->get('database.default', 'mysql');
            error_log("Database connection type: " . $connection);
            
            error_log("Full config: " . json_encode($config, JSON_PRETTY_PRINT));
            
            $dbConfig = $config->get("database.connections.{$connection}");
            error_log("Database config: " . json_encode($dbConfig, JSON_PRETTY_PRINT));
            
            if (!$dbConfig) {
                throw new \RuntimeException("Database configuration not found for connection: {$connection}");
            }
            
            $host = $dbConfig['host'] ?? '127.0.0.1';
            $port = $dbConfig['port'] ?? '3306';
            $dbname = $dbConfig['database'] ?? 'portfolion';
            $username = $dbConfig['username'] ?? 'portfolion';
            $password = $dbConfig['password'] ?? 'portfolion';
            
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname}";
            
            $db = new \PDO($dsn, $username, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            
            // Test the connection
            $db->query('SELECT 1');
            
            return [
                'status' => true,
                'message' => "Successfully connected to database at {$host}:{$port}"
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }
    }
    
    private function checkCache(): array
    {
        $cacheDir = storage_path('cache');
        
        try {
            $testFile = $cacheDir . '/test.txt';
            file_put_contents($testFile, 'test');
            unlink($testFile);
            
            return [
                'status' => true,
                'message' => 'Cache directory is working correctly'
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Cache directory test failed: ' . $e->getMessage()
            ];
        }
    }
    
    private function checkSessions(): array
    {
        $sessionDir = storage_path('framework/sessions');
        
        try {
            $testFile = $sessionDir . '/test.txt';
            file_put_contents($testFile, 'test');
            unlink($testFile);
            
            return [
                'status' => true,
                'message' => 'Session directory is working correctly'
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Session directory test failed: ' . $e->getMessage()
            ];
        }
    }
    
    private function checkComposerDependencies(): array
    {
        $vendorDir = dirname(dirname(dirname(__DIR__))) . '/vendor';
        $autoloadFile = $vendorDir . '/autoload.php';
        
        if (!file_exists($autoloadFile)) {
            return [
                'status' => false,
                'message' => 'Vendor directory or autoload.php not found'
            ];
        }
        
        try {
            require_once $autoloadFile;
            return [
                'status' => true,
                'message' => 'All dependencies are properly installed'
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Dependency check failed: ' . $e->getMessage()
            ];
        }
    }
}
