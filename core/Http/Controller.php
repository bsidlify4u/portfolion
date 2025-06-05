<?php
namespace Portfolion\Http;

use Portfolion\View\TwigTemplate;

class Controller {
    protected ?TwigTemplate $twig = null;
    
    public function __construct() {
        // Initialize Twig
        try {
            $this->twig = new TwigTemplate();
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to initialize Twig: ' . $e->getMessage());
        }
    }
    
    protected function view(string $name, array $data = []): string {
        try {
            return $this->twig->render($name, $data);
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to render view: ' . $e->getMessage());
        }
    }
    
    // Legacy method for PHP templates
    protected function phpView(string $name, array $data = []): string {
        extract($data);
        ob_start();
        include __DIR__ . '/../../app/Views/' . $name . '.php';
        return ob_get_clean();
    }
    
    protected function json($data, int $status = 200): string {
        header('Content-Type: application/json');
        http_response_code($status);
        return json_encode($data);
    }
    
    protected function redirect(string $url): void {
        header("Location: $url");
        exit;
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
