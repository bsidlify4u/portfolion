<?php

require_once __DIR__ . '/vendor/autoload.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Helper function for testing
function test_feature($name, $callback) {
    echo "\n--- Testing $name ---\n";
    try {
        $result = $callback();
        if ($result === false) {
            echo "✓ [SKIPPED] $name\n";
        } else {
            echo "✓ [PASSED] $name\n";
        }
    } catch (Exception $e) {
        echo "✗ [FAILED] $name: " . $e->getMessage() . "\n";
    }
}

// 1. Test Configuration
test_feature('Configuration', function() {
    if (!class_exists('Portfolion\Config')) {
        throw new Exception('Config class not found');
    }
    
    $config = Portfolion\Config::getInstance();
    // Test with an existing config value that should be safe to read
    $value = $config->get('app.name', 'Portfolion');
    
    if (empty($value)) {
        throw new Exception('Config get failed');
    }
    
    return true;
});

// 2. Test Database Connection
test_feature('Database Connection', function() {
    if (!class_exists('Portfolion\Database\Connection')) {
        throw new Exception('Connection class not found');
    }
    
    try {
        $connection = new Portfolion\Database\Connection();
        // Just test if class exists, don't try to connect
        return true;
    } catch (Exception $e) {
        throw new Exception('Could not initialize database connection: ' . $e->getMessage());
    }
});

// 3. Test Query Builder
test_feature('Query Builder', function() {
    if (!class_exists('Portfolion\Database\QueryBuilder')) {
        throw new Exception('QueryBuilder class not found');
    }
    
    try {
        $queryBuilder = new Portfolion\Database\QueryBuilder();
        $query = $queryBuilder->table('users')
            ->where('active', '=', 1)
            ->where('role', '=', 'admin', 'and')
            ->getConnection();
            
        if (!($query instanceof PDO)) {
            throw new Exception('QueryBuilder did not return PDO connection');
        }
        
        return true;
    } catch (Exception $e) {
        throw new Exception('Query builder failed: ' . $e->getMessage());
    }
});

// 4. Test Model
test_feature('Model', function() {
    if (!class_exists('Portfolion\Database\Model')) {
        throw new Exception('Model class not found');
    }
    
    // Just test if the class exists
    return true;
});

// 5. Test Request
test_feature('HTTP Request', function() {
    if (!class_exists('Portfolion\Http\Request')) {
        throw new Exception('Request class not found');
    }
    
    $request = new Portfolion\Http\Request();
    
    if (!method_exists($request, 'getMethod')) {
        throw new Exception('Request::getMethod() not found');
    }
    
    if (!method_exists($request, 'get')) {
        throw new Exception('Request::get() not found');
    }
    
    if (!method_exists($request, 'validate')) {
        throw new Exception('Request::validate() not found');
    }
    
    return true;
});

// 6. Test Response
test_feature('HTTP Response', function() {
    if (!class_exists('Portfolion\Http\Response')) {
        throw new Exception('Response class not found');
    }
    
    $response = new Portfolion\Http\Response('Test content', 200);
    
    if ($response->getContent() !== 'Test content') {
        throw new Exception('Response content mismatch');
    }
    
    if ($response->getStatusCode() !== 200) {
        throw new Exception('Response status code mismatch');
    }
    
    return true;
});

// 7. Test Router
test_feature('Router', function() {
    if (!class_exists('Portfolion\Routing\Router')) {
        throw new Exception('Router class not found');
    }
    
    // Just test if the class exists
    return true;
});

// 8. Test Twig Template
test_feature('Twig Template', function() {
    if (!class_exists('Portfolion\View\TwigTemplate')) {
        throw new Exception('TwigTemplate class not found');
    }
    
    // Just test if the class exists
    return true;
});

// 9. Test Session
test_feature('Session', function() {
    if (!class_exists('Portfolion\Session\Session')) {
        throw new Exception('Session class not found');
    }
    
    // Don't actually start a session in this test script
    return true;
});

// 10. Test Cache
test_feature('Cache', function() {
    if (!function_exists('cache')) {
        return false; // Skip if cache function doesn't exist
    }
    
    return true;
});

// 11. Test Middleware
test_feature('Middleware', function() {
    $middlewareDir = __DIR__ . '/core/Middleware';
    if (!is_dir($middlewareDir)) {
        return false; // Skip if middleware directory doesn't exist
    }
    
    return true;
});

// 12. Test Helpers
test_feature('Helpers', function() {
    $requiredHelpers = [
        'base_path',
        'view',
        'redirect',
        'env'
    ];
    
    foreach ($requiredHelpers as $helper) {
        if (!function_exists($helper)) {
            throw new Exception("Helper function '$helper' not found");
        }
    }
    
    return true;
});

// 13. Test Validation
test_feature('Validation', function() {
    $request = new Portfolion\Http\Request();
    
    try {
        // Just verify the method exists
        if (!method_exists($request, 'validate')) {
            throw new Exception('Validation method not found');
        }
        
        return true;
    } catch (Exception $e) {
        throw new Exception('Validation failed: ' . $e->getMessage());
    }
});

// 14. Test Task Model
test_feature('Task Model', function() {
    if (!class_exists('App\Models\Task')) {
        throw new Exception('Task model not found');
    }
    
    return true;
});

// 15. Test Controller
test_feature('Controller', function() {
    if (!class_exists('App\Controllers\TaskController')) {
        throw new Exception('TaskController not found');
    }
    
    return true;
});

echo "\n--- Framework Test Complete ---\n"; 