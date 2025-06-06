<?php
namespace Portfolion\Http;

use InvalidArgumentException;
use JsonException;
use Portfolion\View\TwigTemplate;
use RuntimeException;

class Response {
    /**
     * @var string Response content
     */
    protected string $content;
    
    /**
     * @var int HTTP status code
     */
    protected int $status;
    
    /**
     * @var array Response headers
     */
    protected array $headers;
    
    /**
     * @var array Flash messages
     */
    protected array $flash = [];
    
    private ?TwigTemplate $twig = null;
    private bool $isSent = false;
    
    /**
     * Response constructor.
     *
     * @param string $content Response content
     * @param int $status HTTP status code
     * @param array $headers Response headers
     */
    public function __construct(string $content = '', int $status = 200, array $headers = []) {
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
    
    /**
     * Set response content
     *
     * @param string $content Response content
     * @return self
     */
    public function setContent(string $content): self {
        $this->content = $content;
        return $this;
    }
    
    /**
     * Get response content
     *
     * @return string
     */
    public function getContent(): string {
        return $this->content;
    }
    
    /**
     * Set HTTP status code
     *
     * @param int $status HTTP status code
     * @return self
     */
    public function setStatus(int $status): self {
        $this->status = $status;
        return $this;
    }
    
    /**
     * Get HTTP status code
     *
     * @return int
     */
    public function getStatus(): int {
        return $this->status;
    }
    
    /**
     * Set response header
     *
     * @param string $name Header name
     * @param string $value Header value
     * @return self
     */
    public function setHeader(string $name, string $value): self {
        $this->headers[$name] = $value;
        return $this;
    }
    
    /**
     * Set multiple response headers
     *
     * @param array $headers Response headers
     * @return self
     */
    public function setHeaders(array $headers): self {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }
    
    /**
     * Get response headers
     *
     * @return array
     */
    public function getHeaders(): array {
        return $this->headers;
    }
    
    /**
     * Add flash message
     *
     * @param string $key Flash key
     * @param mixed $value Flash value
     * @return self
     */
    public function with(string $key, $value): self {
        $this->flash[$key] = $value;
        
        if (isset($_SESSION)) {
            $_SESSION['_flash'][$key] = $value;
        }
        
        return $this;
    }
    
    /**
     * Send the response
     *
     * @return void
     */
    public function send(): void {
        if ($this->isSent) {
            throw new RuntimeException('Response has already been sent');
        }
        
        if (!headers_sent()) {
            http_response_code($this->status);
            
            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }
        
        echo $this->content;
        
        $this->isSent = true;
        
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }
    
    /**
     * Create a JSON response
     *
     * @param mixed $data Response data
     * @param int $status HTTP status code
     * @param array $headers Response headers
     * @return self
     */
    public static function json($data, int $status = 200, array $headers = []): self {
        $headers = array_merge(['Content-Type' => 'application/json'], $headers);
        
        return new self(json_encode($data), $status, $headers);
    }
    
    /**
     * Create a redirect response
     *
     * @param string $url URL to redirect to
     * @param int $status HTTP status code
     * @return self
     */
    public static function redirect(string $url, int $status = 302): self {
        return new self('', $status, ['Location' => $url]);
    }
    
    /**
     * Create a file download response
     *
     * @param string $path File path
     * @param string|null $name File name
     * @param array $headers Response headers
     * @return self
     */
    public static function download(string $path, ?string $name = null, array $headers = []): self {
        if (!file_exists($path)) {
            throw new \Exception("File not found: {$path}");
        }
        
        $filename = $name ?? basename($path);
        $mime = mime_content_type($path) ?: 'application/octet-stream';
        
        $headers = array_merge([
            'Content-Type' => $mime,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => filesize($path),
        ], $headers);
        
        return new self(file_get_contents($path), 200, $headers);
    }
    
    public function view(string $name, array $data = [], int $status = 200): self {
        $this->addHeader('Content-Type', 'text/html; charset=utf-8');
        
        try {
            // Use Twig for rendering
            $content = $this->twig->render($name, $data);
            $this->setContent($content);
            $this->setStatus($status);
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
        $this->setStatus($status);
        return $this;
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
    
    private function normalizeHeaderName(string $name): string {
        $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));
        return preg_replace_callback('/([A-Z][a-z]+)/', function($matches) {
            return ucfirst(strtolower($matches[1]));
        }, $name);
    }
}

