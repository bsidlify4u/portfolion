<?php
namespace Portfolion\Http;

use Portfolion\View\ViewManager;

abstract class Controller
{
    /**
     * Render a view
     *
     * @param string $view View name
     * @param array $data View data
     * @param string|null $engine View engine (null for default)
     * @return Response
     */
    protected function view(string $view, array $data = [], ?string $engine = null): Response
    {
        $viewManager = ViewManager::getInstance();
        $content = $viewManager->render($view, $data, $engine);
        
        return new Response($content);
    }
    
    /**
     * Return JSON response
     *
     * @param mixed $data Response data
     * @param int $status HTTP status code
     * @param array $headers Response headers
     * @return Response
     */
    protected function json($data, int $status = 200, array $headers = []): Response
    {
        $response = new Response(
            json_encode($data),
            $status,
            array_merge(['Content-Type' => 'application/json'], $headers)
        );
        
        return $response;
    }
    
    /**
     * Redirect to a URL
     *
     * @param string $url URL to redirect to
     * @param int $status HTTP status code
     * @param array $headers Response headers
     * @return Response
     */
    protected function redirect(string $url, int $status = 302, array $headers = []): Response
    {
        $response = new Response('', $status, array_merge(['Location' => $url], $headers));
        
        return $response;
    }
    
    /**
     * Return a file download response
     *
     * @param string $path File path
     * @param string|null $name File name
     * @param array $headers Response headers
     * @return Response
     */
    protected function download(string $path, ?string $name = null, array $headers = []): Response
    {
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
        
        $response = new Response(file_get_contents($path), 200, $headers);
        
        return $response;
    }
    
    // Legacy method for PHP templates
    protected function phpView(string $name, array $data = []): string {
        extract($data);
        ob_start();
        include __DIR__ . '/../../app/Views/' . $name . '.php';
        return ob_get_clean();
    }
    
    protected function validate(array $data, array $rules): array {
        $errors = [];
        foreach ($rules as $field => $rule) {
            if (!isset($data[$field])) {
                $errors[$field] = "Field is required";
                continue;
            }
            
            $value = $data[$field];
            $rulesList = explode('|', $rule);
            
            foreach ($rulesList as $ruleItem) {
                if ($ruleItem === 'required' && empty($value)) {
                    $errors[$field] = "Field is required";
                } elseif ($ruleItem === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = "Invalid email format";
                } elseif (strpos($ruleItem, 'min:') === 0) {
                    $min = substr($ruleItem, 4);
                    if (strlen($value) < $min) {
                        $errors[$field] = "Minimum length is $min";
                    }
                }
                // Add more validation rules as needed
            }
        }
        
        return $errors;
    }
    
    protected function csrf(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
