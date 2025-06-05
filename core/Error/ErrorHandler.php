<?php

namespace Portfolion\Error;

use Throwable;
use ErrorException;
use Portfolion\Config;
use Portfolion\Http\Response;
use Portfolion\View\ViewFactory;
use Portfolion\Logging\Logger;

/**
 * Centralized error handler for the Portfolion framework
 * 
 * This class handles all errors, exceptions, and fatal errors in the application.
 * It provides different handling strategies based on the environment (development vs production).
 */
class ErrorHandler
{
    /**
     * @var self|null Singleton instance
     */
    private static ?self $instance = null;
    
    /**
     * @var Config Configuration instance
     */
    private Config $config;
    
    /**
     * @var Logger Logger instance
     */
    private Logger $logger;
    
    /**
     * @var bool Whether we're in debug mode
     */
    private bool $debug;
    
    /**
     * @var bool Whether to display errors
     */
    private bool $displayErrors;
    
    /**
     * @var bool Whether to log errors
     */
    private bool $logErrors;
    
    /**
     * @var int Error reporting level
     */
    private int $errorReporting;
    
    /**
     * @var string Path to the error log file
     */
    private string $errorLogPath;
    
    /**
     * @var array<int, string> Error levels map
     */
    private array $errorLevels = [
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Strict',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated',
    ];
    
    /**
     * Private constructor for singleton pattern
     */
    private function __construct()
    {
        $this->config = Config::getInstance();
        
        // Get environment-specific settings
        $env = $this->config->get('env.current', 'local');
        $envConfig = $this->config->get('env.environments.' . $env, []);
        
        // Set up error handling configuration
        $this->debug = $envConfig['debug'] ?? $this->config->get('app.debug', true);
        $this->displayErrors = $envConfig['display_errors'] ?? true;
        $this->logErrors = $envConfig['log_errors'] ?? true;
        $this->errorReporting = $envConfig['error_reporting'] ?? E_ALL;
        $this->errorLogPath = $this->config->get('logging.channels.error.path', 'storage/logs/error.log');
        
        // Create logger instance
        $this->logger = new Logger('error');
        
        // Set PHP error reporting level
        error_reporting($this->errorReporting);
        ini_set('display_errors', $this->displayErrors ? '1' : '0');
        ini_set('log_errors', $this->logErrors ? '1' : '0');
        ini_set('error_log', $this->errorLogPath);
    }
    
    /**
     * Get the singleton instance
     * 
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Register all error handlers
     * 
     * @return void
     */
    public function register(): void
    {
        // Set error handler
        set_error_handler([$this, 'handleError']);
        
        // Set exception handler
        set_exception_handler([$this, 'handleException']);
        
        // Register shutdown function to catch fatal errors
        register_shutdown_function([$this, 'handleShutdown']);
    }
    
    /**
     * Handle PHP errors
     * 
     * @param int $level Error level
     * @param string $message Error message
     * @param string $file File where the error occurred
     * @param int $line Line where the error occurred
     * @return bool Whether the error was handled
     * @throws ErrorException
     */
    public function handleError(int $level, string $message, string $file, int $line): bool
    {
        // Respect error_reporting settings
        if (!(error_reporting() & $level)) {
            return false;
        }
        
        // Log the error
        if ($this->logErrors) {
            $levelName = $this->errorLevels[$level] ?? 'Unknown Error';
            $this->logger->log($levelName, $message, [
                'file' => $file,
                'line' => $line
            ]);
        }
        
        // Convert errors to exceptions
        throw new ErrorException($message, 0, $level, $file, $line);
    }
    
    /**
     * Handle uncaught exceptions
     * 
     * @param Throwable $exception The uncaught exception
     * @return void
     */
    public function handleException(Throwable $exception): void
    {
        // Log the exception
        if ($this->logErrors) {
            $this->logger->error($exception->getMessage(), [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ]);
        }
        
        // Send appropriate response based on environment
        $this->sendExceptionResponse($exception);
    }
    
    /**
     * Handle fatal errors
     * 
     * @return void
     */
    public function handleShutdown(): void
    {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            // Log the fatal error
            if ($this->logErrors) {
                $levelName = $this->errorLevels[$error['type']] ?? 'Fatal Error';
                $this->logger->critical($error['message'], [
                    'type' => $levelName,
                    'file' => $error['file'],
                    'line' => $error['line']
                ]);
            }
            
            // Send appropriate response
            $this->sendFatalErrorResponse($error);
        }
    }
    
    /**
     * Send an appropriate response for an uncaught exception
     * 
     * @param Throwable $exception The exception
     * @return void
     */
    private function sendExceptionResponse(Throwable $exception): void
    {
        $statusCode = 500;
        
        // Determine HTTP status code based on exception type
        if (method_exists($exception, 'getStatusCode')) {
            $statusCode = $exception->getStatusCode();
        }
        
        // Create response based on environment
        if ($this->debug && $this->displayErrors) {
            $response = $this->createDebugResponse($exception, $statusCode);
        } else {
            $response = $this->createProductionResponse($exception, $statusCode);
        }
        
        // Send the response
        echo $response->getContent();
        exit($statusCode);
    }
    
    /**
     * Send an appropriate response for a fatal error
     * 
     * @param array $error The fatal error details
     * @return void
     */
    private function sendFatalErrorResponse(array $error): void
    {
        $statusCode = 500;
        
        // Create response based on environment
        if ($this->debug && $this->displayErrors) {
            $content = $this->renderDebugErrorPage([
                'type' => $this->errorLevels[$error['type']] ?? 'Fatal Error',
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
                'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
            ]);
        } else {
            $content = $this->renderErrorPage($statusCode);
        }
        
        // Send the response
        http_response_code($statusCode);
        echo $content;
        exit($statusCode);
    }
    
    /**
     * Create a debug response with detailed error information
     * 
     * @param Throwable $exception The exception
     * @param int $statusCode HTTP status code
     * @return Response
     */
    private function createDebugResponse(Throwable $exception, int $statusCode): Response
    {
        $content = $this->renderDebugErrorPage([
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTrace()
        ]);
        
        return new Response($content, $statusCode, [
            'Content-Type' => 'text/html; charset=UTF-8'
        ]);
    }
    
    /**
     * Create a production response with minimal error information
     * 
     * @param Throwable $exception The exception
     * @param int $statusCode HTTP status code
     * @return Response
     */
    private function createProductionResponse(Throwable $exception, int $statusCode): Response
    {
        $content = $this->renderErrorPage($statusCode);
        
        return new Response($content, $statusCode, [
            'Content-Type' => 'text/html; charset=UTF-8'
        ]);
    }
    
    /**
     * Render a debug error page with detailed information
     * 
     * @param array $error Error details
     * @return string HTML content
     */
    private function renderDebugErrorPage(array $error): string
    {
        // Try to use the view system if available
        try {
            $viewFactory = new ViewFactory();
            return $viewFactory->make('errors.debug', $error);
        } catch (Throwable $e) {
            // Fallback to basic HTML if view rendering fails
            return $this->getDebugErrorTemplate($error);
        }
    }
    
    /**
     * Render a production error page
     * 
     * @param int $statusCode HTTP status code
     * @return string HTML content
     */
    private function renderErrorPage(int $statusCode): string
    {
        // Try to use the view system if available
        try {
            $viewFactory = new ViewFactory();
            return $viewFactory->make("errors.{$statusCode}", []);
        } catch (Throwable $e) {
            // Try generic error page
            try {
                return $viewFactory->make('errors.error', ['statusCode' => $statusCode]);
            } catch (Throwable $e) {
                // Fallback to basic HTML if view rendering fails
                return $this->getErrorTemplate($statusCode);
            }
        }
    }
    
    /**
     * Get a basic HTML template for debug error pages
     * 
     * @param array $error Error details
     * @return string HTML content
     */
    private function getDebugErrorTemplate(array $error): string
    {
        $title = htmlspecialchars($error['type']);
        $message = htmlspecialchars($error['message']);
        $file = htmlspecialchars($error['file']);
        $line = $error['line'];
        
        $traceHtml = '';
        if (isset($error['trace']) && is_array($error['trace'])) {
            foreach ($error['trace'] as $i => $trace) {
                $class = isset($trace['class']) ? htmlspecialchars($trace['class']) : '';
                $type = isset($trace['type']) ? htmlspecialchars($trace['type']) : '';
                $function = isset($trace['function']) ? htmlspecialchars($trace['function']) : '';
                $traceFile = isset($trace['file']) ? htmlspecialchars($trace['file']) : 'unknown';
                $traceLine = isset($trace['line']) ? $trace['line'] : 0;
                
                $traceHtml .= "<tr>
                    <td>{$i}</td>
                    <td>{$class}{$type}{$function}()</td>
                    <td>{$traceFile}:{$traceLine}</td>
                </tr>";
            }
        }
        
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error: {$title}</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; line-height: 1.5; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 1200px; margin: 0 auto; }
        .error-box { background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; padding: 20px; margin-bottom: 20px; }
        .error-title { color: #721c24; margin-top: 0; }
        .error-message { font-size: 18px; margin-bottom: 20px; }
        .error-location { background-color: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; }
        .stack-trace { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .stack-trace th, .stack-trace td { text-align: left; padding: 8px; border-bottom: 1px solid #ddd; }
        .stack-trace th { background-color: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-box">
            <h1 class="error-title">{$title}</h1>
            <div class="error-message">{$message}</div>
            <div class="error-location">{$file}:{$line}</div>
        </div>
        
        <h2>Stack Trace</h2>
        <table class="stack-trace">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Function</th>
                    <th>Location</th>
                </tr>
            </thead>
            <tbody>
                {$traceHtml}
            </tbody>
        </table>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Get a basic HTML template for production error pages
     * 
     * @param int $statusCode HTTP status code
     * @return string HTML content
     */
    private function getErrorTemplate(int $statusCode): string
    {
        $title = match ($statusCode) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            500 => 'Internal Server Error',
            503 => 'Service Unavailable',
            default => 'Error'
        };
        
        $message = match ($statusCode) {
            400 => 'The server could not understand the request due to invalid syntax.',
            401 => 'Authentication is required and has failed or has not been provided.',
            403 => 'You do not have permission to access this resource.',
            404 => 'The requested resource could not be found on this server.',
            500 => 'The server encountered an unexpected condition that prevented it from fulfilling the request.',
            503 => 'The server is currently unavailable. Please try again later.',
            default => 'An error occurred while processing your request.'
        };
        
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$statusCode} - {$title}</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; line-height: 1.5; margin: 0; padding: 0; display: flex; align-items: center; justify-content: center; min-height: 100vh; background-color: #f8f9fa; }
        .container { max-width: 500px; padding: 40px; background-color: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .error-code { font-size: 72px; font-weight: bold; margin: 0; color: #dc3545; }
        .error-title { margin: 10px 0 20px; color: #343a40; }
        .error-message { color: #6c757d; margin-bottom: 30px; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px; transition: background-color 0.2s; }
        .btn:hover { background-color: #0069d9; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="error-code">{$statusCode}</h1>
        <h2 class="error-title">{$title}</h2>
        <p class="error-message">{$message}</p>
        <a href="/" class="btn">Go to Homepage</a>
    </div>
</body>
</html>
HTML;
    }
} 