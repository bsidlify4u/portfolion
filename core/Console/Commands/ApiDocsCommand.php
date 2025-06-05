<?php

namespace Portfolion\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Portfolion\Routing\Router;
use Portfolion\Documentation\OpenApiGenerator;

class ApiDocsCommand extends BaseCommand
{
    /**
     * The name of the console command.
     */
    protected static $defaultName = 'api:docs';

    /**
     * The console command description.
     */
    protected static $defaultDescription = 'Generate API documentation using OpenAPI';
    
    /**
     * Define command options
     */
    protected $options = [
        'format' => 'Output format (json or yaml)',
        'output' => 'Output file path',
        'serve' => 'Serve documentation using Swagger UI',
        'port' => 'Port to use when serving documentation'
    ];

    /**
     * Execute the console command.
     */
    protected function handle(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $format = $input->getOption('format') ?: 'json';
        $outputPath = $input->getOption('output');
        $serve = $input->getOption('serve');
        $port = $input->getOption('port') ?: 8000;
        
        if (!in_array($format, ['json', 'yaml'])) {
            $io->error('Invalid format specified. Use either "json" or "yaml".');
            return Command::FAILURE;
        }
        
        if (!$outputPath) {
            $outputPath = 'public/api-docs.' . $format;
        }
        
        $router = app()->get(Router::class);
        $generator = new OpenApiGenerator($router);
        
        $io->title('Generating API Documentation');
        
        try {
            if ($format === 'json') {
                $success = $generator->exportJson($outputPath);
            } else {
                $success = $generator->exportYaml($outputPath);
            }
            
            if ($success) {
                $io->success("API documentation generated successfully at {$outputPath}");
                
                if ($serve) {
                    $this->serveDocumentation($io, $outputPath, $port);
                    return Command::SUCCESS;
                }
                
                return Command::SUCCESS;
            } else {
                $io->error("Failed to write documentation to {$outputPath}");
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $io->error('Error generating API documentation: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Serve the API documentation using Swagger UI
     * 
     * @param SymfonyStyle $io
     * @param string $docsPath
     * @param int $port
     */
    protected function serveDocumentation(SymfonyStyle $io, string $docsPath, int $port): void
    {
        // Create a temporary HTML file with Swagger UI
        $swaggerUiHtml = $this->getSwaggerUiHtml($docsPath);
        $tempDir = sys_get_temp_dir() . '/api-docs-' . uniqid();
        
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        $htmlPath = $tempDir . '/index.html';
        file_put_contents($htmlPath, $swaggerUiHtml);
        
        // Copy the API docs file to the temp directory
        copy($docsPath, $tempDir . '/' . basename($docsPath));
        
        $io->section('Starting API Documentation Server');
        $io->info("Swagger UI is available at http://localhost:{$port}");
        $io->info('Press Ctrl+C to stop the server');
        
        // Start the PHP built-in server
        $command = sprintf(
            'php -S localhost:%d -t %s',
            $port,
            escapeshellarg($tempDir)
        );
        
        passthru($command);
        
        // Clean up temporary files when the server stops
        if (is_dir($tempDir)) {
            $this->removeDirectory($tempDir);
        }
    }
    
    /**
     * Get the Swagger UI HTML template
     * 
     * @param string $docsPath
     * @return string
     */
    protected function getSwaggerUiHtml(string $docsPath): string
    {
        $docsUrl = basename($docsPath);
        
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@4.5.0/swagger-ui.css" />
    <style>
        body {
            margin: 0;
            padding: 0;
        }
        #swagger-ui {
            max-width: 1460px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>

    <script src="https://unpkg.com/swagger-ui-dist@4.5.0/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@4.5.0/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: "{$docsUrl}",
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                layout: "StandaloneLayout",
                defaultModelsExpandDepth: -1
            });
            window.ui = ui;
        }
    </script>
</body>
</html>
HTML;
    }
    
    /**
     * Remove a directory and its contents recursively
     * 
     * @param string $dir
     * @return bool
     */
    protected function removeDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }
        
        $objects = scandir($dir);
        
        foreach ($objects as $object) {
            if ($object == "." || $object == "..") {
                continue;
            }
            
            $path = $dir . '/' . $object;
            
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        return rmdir($dir);
    }
} 