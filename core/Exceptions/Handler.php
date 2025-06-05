<?php
namespace Portfolion\Exceptions;

use Core\Auth\AuthenticationException;
use Core\Security\ValidationException;
use Portfolion\Http\Response;
use Throwable;
use Whoops\Run as Whoops;
use Psr\Log\LoggerInterface;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\JsonResponseHandler;
use Portfolion\Http\Request;

class Handler {
    /**
     * List of exceptions that should not be reported
     */
    protected array $dontReport = [];
    
    /**
     * Logger instance
     */
    protected LoggerInterface $logger;
    
    /**
     * Current environment
     */
    protected string $environment;
    
    /**
     * Create a new exception handler
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->environment = env('APP_ENV', 'production');
    }
    
    /**
     * Report an exception
     */
    public function report(Throwable $e): void
    {
        if ($this->shouldntReport($e)) {
            return;
        }
        
        if ($this->environment === 'production') {
            $this->logException($e);
        }
    }
    
    /**
     * Log the exception to the configured logging stack
     */
    protected function logException(Throwable $e): void
    {
        $this->logger->error($e->getMessage(), [
            'exception' => $e,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
    }
    
    /**
     * Determine if the exception should not be reported
     */
    protected function shouldntReport(Throwable $e): bool
    {
        foreach ($this->dontReport as $type) {
            if ($e instanceof $type) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Render an exception into an HTTP response
     */
    public function render(Request $request, Throwable $e): Response
    {
        if ($request->expectsJson()) {
            return $this->renderExceptionAsJson($e);
        }
        
        return $this->renderExceptionAsHtml($e);
    }
    
    /**
     * Render an exception as HTML
     */
    protected function renderExceptionAsHtml(Throwable $e): Response
    {
        if ($this->environment === 'local') {
            return $this->renderExceptionWithWhoops($e);
        }
        
        // In production, show a generic error page
        return new Response(
            view('errors.500', ['exception' => $e]),
            500
        );
    }
    
    /**
     * Render an exception as JSON
     */
    protected function renderExceptionAsJson(Throwable $e): Response
    {
        $data = [
            'error' => true,
            'message' => $this->environment === 'production' 
                ? 'Server Error' 
                : $e->getMessage(),
            'code' => $e->getCode()
        ];
        
        if ($this->environment !== 'production') {
            $data['file'] = $e->getFile();
            $data['line'] = $e->getLine();
            $data['trace'] = $e->getTrace();
        }
        
        return new Response(json_encode($data), 500, [
            'Content-Type' => 'application/json'
        ]);
    }
    
    /**
     * Render an exception using Whoops
     */
    protected function renderExceptionWithWhoops(Throwable $e): Response
    {
        $whoops = new Whoops();
        
        $whoops->pushHandler(new PrettyPageHandler());
        $whoops->allowQuit(false);
        $whoops->writeToOutput(false);
        
        $output = $whoops->handleException($e);
        
        return new Response($output, 500);
    }
    
    /**
     * Register the error handling for CLI environment
     */
    public function registerForConsole(): void
    {
        error_reporting(E_ALL);
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }
    
    /**
     * Register the error handling for HTTP requests
     */
    public function register(): void
    {
        error_reporting(E_ALL);
        
        if ($this->environment === 'local') {
            $this->registerWhoops();
            return;
        }
        
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }
    
    /**
     * Register Whoops for development environment
     */
    protected function registerWhoops(): void
    {
        $whoops = new Whoops();
        $whoops->pushHandler(new PrettyPageHandler());
        $whoops->register();
    }
    
    /**
     * Handle a PHP error
     */
    public function handleError($level, $message, $file = '', $line = 0): bool
    {
        if (error_reporting() & $level) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
        
        return true;
    }
    
    /**
     * Handle an exception
     */
    public function handleException(Throwable $e): void
    {
        $this->report($e);
        
        if (php_sapi_name() === 'cli') {
            $this->renderForConsole($e);
        } else {
            $this->renderHttpResponse($e);
        }
    }
    
    /**
     * Render an exception for the console
     */
    protected function renderForConsole(Throwable $e): void
    {
        fwrite(STDERR, "\n" . $e->getMessage() . "\n");
        fwrite(STDERR, "\n" . $e->getTraceAsString() . "\n");
    }
    
    /**
     * Render an HTTP response for an exception
     */
    protected function renderHttpResponse(Throwable $e): void
    {
        $request = Request::capture();
        $response = $this->render($request, $e);
        
        $response->send();
    }
    
    /**
     * Handle PHP shutdown and catch fatal errors
     */
    public function handleShutdown(): void
    {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $this->handleError(
                $error['type'],
                $error['message'],
                $error['file'],
                $error['line']
            );
        }
    }
}
