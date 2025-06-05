<?php
namespace Portfolion\Http;

use InvalidArgumentException;
use JsonException;
use Portfolion\View\TwigTemplate;
use RuntimeException;

class Response {
    private mixed $content = '';
    private int $status = 200;
    private array $headers = [];
    private ?TwigTemplate $twig = null;
    private bool $isSent = false;
    
    public function __construct(mixed $content = '', int $status = 200, array $headers = []) {
        $this->content = $content;
        $this->status = $status;
        $this->headers = $headers;
        
        // Initialize Twig
        try {
            $this->twig = new TwigTemplate();
        } catch (\Exception $e) {
            throw new RuntimeException('Failed to initialize Twig: ' . $e->getMessage());
        }
    }
    
    public function setContent(mixed $content): self {
        $this->content = $content;
        return $this;
    }
    
    public function getContent(): mixed {
        return $this->content;
    }
    
    public function setStatusCode(int $status): self {
        if ($status < 100 || $status > 599) {
            throw new InvalidArgumentException('HTTP status code must be between 100 and 599');
        }
        $this->status = $status;
        return $this;
    }
    
    public function getStatusCode(): int {
        return $this->status;
    }
    
    public function addHeader(string $key, string $value): self {
        $this->headers[$this->normalizeHeaderName($key)] = $value;
        return $this;
    }
    
    public function removeHeader(string $key): self {
        unset($this->headers[$this->normalizeHeaderName($key)]);
        return $this;
    }
    
    public function getHeader(string $key): ?string {
        return $this->headers[$this->normalizeHeaderName($key)] ?? null;
    }
    
    public function getHeaders(): array {
        return $this->headers;
    }
    
    public function json(mixed $data, int $status = 200, int $flags = 0): self {
        try {
            $content = json_encode($data, JSON_THROW_ON_ERROR | $flags);
        } catch (JsonException $e) {
            throw new RuntimeException('Failed to encode response as JSON: ' . $e->getMessage());
        }
        
        $this->addHeader('Content-Type', 'application/json; charset=utf-8');
        $this->setContent($content);
        $this->setStatusCode($status);
        
        return $this;
    }
    
    public function view(string $name, array $data = [], int $status = 200): self {
        $this->addHeader('Content-Type', 'text/html; charset=utf-8');
        
        try {
            // Use Twig for rendering
            $content = $this->twig->render($name, $data);
            $this->setContent($content);
            $this->setStatusCode($status);
        } catch (\Exception $e) {
            throw new RuntimeException('Failed to render view: ' . $e->getMessage());
        }
        
        return $this;
    }
    
    // Legacy method for PHP templates
    public function phpView(string $name, array $data = [], int $status = 200): self {
        $this->addHeader('Content-Type', 'text/html; charset=utf-8');
        
        // Use PHP views
        ob_start();
        extract($data);
        include dirname(dirname(__DIR__)) . '/app/Views/' . $name . '.php';
        $content = ob_get_clean();
        
        $this->setContent($content);
        $this->setStatusCode($status);
        return $this;
    }
    
    public function redirect(string $url, int $status = 302): void {
        if ($status < 300 || $status > 308) {
            throw new InvalidArgumentException('Invalid redirect status code');
        }
        
        $this->addHeader('Location', filter_var($url, FILTER_VALIDATE_URL) ? $url : '/' . ltrim($url, '/'));
        $this->setStatusCode($status);
        $this->send();
    }
    
    public function download(string $path, ?string $name = null, bool $inline = false): void {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('File not found or not readable: ' . $path);
        }
        
        $name = $name ?? basename($path);
        $mimeType = mime_content_type($path) ?: 'application/octet-stream';
        
        $this->addHeader('Content-Type', $mimeType);
        $this->addHeader('Content-Disposition', ($inline ? 'inline' : 'attachment') . '; filename="' . str_replace('"', '\\"', $name) . '"');
        $this->addHeader('Content-Length', (string) filesize($path));
        $this->addHeader('Cache-Control', 'private, no-transform, no-store, must-revalidate');
        
        $this->setContent(function() use ($path) {
            $handle = fopen($path, 'rb');
            if ($handle === false) {
                throw new RuntimeException('Failed to open file: ' . $path);
            }
            
            while (!feof($handle)) {
                yield fread($handle, 8192);
            }
            
            fclose($handle);
        });
        
        $this->send();
    }
    
    public function send(): void {
        if ($this->isSent) {
            throw new RuntimeException('Response has already been sent');
        }
        
        if (!headers_sent()) {
            http_response_code($this->status);
            
            foreach ($this->headers as $key => $value) {
                header($key . ': ' . $value, true);
            }
        }
        
        if (is_callable($this->content)) {
            foreach (($this->content)() as $chunk) {
                echo $chunk;
                if (connection_aborted()) {
                    break;
                }
            }
        } elseif (is_string($this->content) || is_numeric($this->content)) {
            echo $this->content;
        } elseif (!is_null($this->content)) {
            throw new RuntimeException('Invalid response content type');
        }
        
        $this->isSent = true;
        
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }
    
    private function normalizeHeaderName(string $name): string {
        $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));
        return preg_replace_callback('/([A-Z][a-z]+)/', function($matches) {
            return ucfirst(strtolower($matches[1]));
        }, $name);
    }
    
    /**
     * Flash a message to the session.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function with(string $key, mixed $value): self
    {
        if (function_exists('session')) {
            session()->flash($key, $value);
        } else {
            // Fallback if session helper is not available
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['flash'][$key] = $value;
        }
        
        return $this;
    }
}
