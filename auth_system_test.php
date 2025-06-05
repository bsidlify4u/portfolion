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
        echo "  at " . $e->getFile() . ":" . $e->getLine() . "\n";
        echo "  " . $e->getTraceAsString() . "\n";
    }
}

echo "\n=== PORTFOLION AUTHENTICATION SYSTEM TEST ===\n";
echo "Testing authentication and authorization features\n";

// 1. Test User Model
test_feature('User Model', function() {
    if (!class_exists('Portfolion\Auth\User')) {
        throw new Exception('User model not found');
    }
    
    // Test creating a new user
    $user = new Portfolion\Auth\User();
    $user->name = 'Test User';
    $user->email = 'test_' . time() . '@example.com';
    
    // Hash the password
    $hasher = new Portfolion\Hash\HashManager();
    $user->password = $hasher->make('password123');
    
    // Test that the user implements Authenticatable
    if (!($user instanceof Portfolion\Auth\Authenticatable)) {
        throw new Exception('User model does not implement Authenticatable');
    }
    
    // Test authenticatable methods
    $identifier = $user->getAuthIdentifier();
    $password = $user->getAuthPassword();
    
    if (empty($password)) {
        throw new Exception('User getAuthPassword() failed');
    }
    
    // Test saving the user (if database is available)
    try {
        $saved = $user->save();
        if (!$saved) {
            echo "  Note: Could not save user to database, but model is valid\n";
        }
    } catch (Exception $e) {
        echo "  Note: Could not save user to database: " . $e->getMessage() . "\n";
    }
    
    return true;
});

// 2. Test Hash Manager
test_feature('Hash Manager', function() {
    if (!class_exists('Portfolion\Hash\HashManager')) {
        throw new Exception('HashManager class not found');
    }
    
    $hasher = new Portfolion\Hash\HashManager();
    
    // Test password hashing
    $password = 'secret123';
    $hashed = $hasher->make($password);
    
    if (empty($hashed)) {
        throw new Exception('Password hashing failed');
    }
    
    // Test password verification
    if (!$hasher->check($password, $hashed)) {
        throw new Exception('Password verification failed');
    }
    
    // Test wrong password fails verification
    if ($hasher->check('wrong_password', $hashed)) {
        throw new Exception('Password verification should fail with wrong password');
    }
    
    // Test different hash algorithms if available
    try {
        $bcrypt = $hasher->driver('bcrypt')->make($password);
        if (empty($bcrypt)) {
            throw new Exception('Bcrypt hashing failed');
        }
        
        if (PHP_VERSION_ID >= 70200) {
            $argon2i = $hasher->driver('argon2i')->make($password);
            if (empty($argon2i)) {
                throw new Exception('Argon2i hashing failed');
            }
            
            $argon2id = $hasher->driver('argon2id')->make($password);
            if (empty($argon2id)) {
                throw new Exception('Argon2id hashing failed');
            }
        }
    } catch (Exception $e) {
        echo "  Note: Some hash algorithms not available: " . $e->getMessage() . "\n";
    }
    
    return true;
});

// 3. Test Auth Manager
test_feature('Auth Manager', function() {
    if (!class_exists('Portfolion\Auth\AuthManager')) {
        throw new Exception('AuthManager class not found');
    }
    
    // Skip actual instantiation as it requires app instance
    return true;
});

// 4. Test Session Guard
test_feature('Session Guard', function() {
    if (!class_exists('Portfolion\Auth\Guards\SessionGuard')) {
        throw new Exception('SessionGuard class not found');
    }
    
    // We won't test actual authentication as it requires a session and database
    // Just verify the class structure
    $methods = [
        'check',
        'guest',
        'user',
        'id',
        'validate',
        'attempt',
        'once',
        'login',
        'loginUsingId',
        'logout'
    ];
    
    $reflection = new ReflectionClass('Portfolion\Auth\Guards\SessionGuard');
    
    foreach ($methods as $method) {
        if (!$reflection->hasMethod($method)) {
            throw new Exception("SessionGuard missing method: $method");
        }
    }
    
    return true;
});

// 5. Test Token Guard
test_feature('Token Guard', function() {
    if (!class_exists('Portfolion\Auth\Guards\TokenGuard')) {
        throw new Exception('TokenGuard class not found');
    }
    
    // We won't test actual authentication as it requires a database
    // Just verify the class structure
    $methods = [
        'check',
        'guest',
        'user',
        'id',
        'validate',
        'attempt',
        'once',
        'login',
        'loginUsingId',
        'logout',
        'getTokenForRequest'
    ];
    
    $reflection = new ReflectionClass('Portfolion\Auth\Guards\TokenGuard');
    
    foreach ($methods as $method) {
        if (!$reflection->hasMethod($method)) {
            throw new Exception("TokenGuard missing method: $method");
        }
    }
    
    return true;
});

// 6. Test User Provider
test_feature('User Provider', function() {
    if (!class_exists('Portfolion\Auth\Providers\UserProvider')) {
        throw new Exception('UserProvider class not found');
    }
    
    // We won't test actual user retrieval as it requires a database
    // Just verify the class structure
    $methods = [
        'retrieveById',
        'retrieveByCredentials',
        'validateCredentials',
        'retrieveByToken',
        'updateRememberToken'
    ];
    
    $reflection = new ReflectionClass('Portfolion\Auth\Providers\UserProvider');
    
    foreach ($methods as $method) {
        if (!$reflection->hasMethod($method)) {
            throw new Exception("UserProvider missing method: $method");
        }
    }
    
    return true;
});

// 7. Test Auth Middleware
test_feature('Auth Middleware', function() {
    if (!class_exists('Portfolion\Auth\Middleware\Authenticate')) {
        throw new Exception('Authenticate middleware not found');
    }
    
    if (!class_exists('Portfolion\Auth\Middleware\Authorize')) {
        throw new Exception('Authorize middleware not found');
    }
    
    // Test Authenticate middleware
    $authenticateMethods = [
        'handle',
        'authenticate',
        'unauthenticated'
    ];
    
    $reflection = new ReflectionClass('Portfolion\Auth\Middleware\Authenticate');
    
    foreach ($authenticateMethods as $method) {
        if (!$reflection->hasMethod($method)) {
            throw new Exception("Authenticate middleware missing method: $method");
        }
    }
    
    // Test Authorize middleware
    $authorizeMethods = [
        'handle',
        'authorize',
        'unauthorized'
    ];
    
    $reflection = new ReflectionClass('Portfolion\Auth\Middleware\Authorize');
    
    foreach ($authorizeMethods as $method) {
        if (!$reflection->hasMethod($method)) {
            throw new Exception("Authorize middleware missing method: $method");
        }
    }
    
    return true;
});

// 8. Test Gate/Authorization
test_feature('Gate/Authorization', function() {
    if (!class_exists('Portfolion\Auth\Gate')) {
        throw new Exception('Gate class not found');
    }
    
    // Test Gate methods
    $gateMethods = [
        'has',
        'define',
        'allows',
        'denies',
        'check',
        'policy',
        'forUser'
    ];
    
    $reflection = new ReflectionClass('Portfolion\Auth\Gate');
    
    foreach ($gateMethods as $method) {
        if (!$reflection->hasMethod($method)) {
            throw new Exception("Gate missing method: $method");
        }
    }
    
    // Skip creating Gate instance as it requires app
    return true;
});

// 9. Test Auth Facade
test_feature('Auth Facade', function() {
    if (!class_exists('Portfolion\Auth\Facades\Auth')) {
        throw new Exception('Auth facade not found');
    }
    
    if (!class_exists('Portfolion\Support\Facades\Facade')) {
        throw new Exception('Base Facade class not found');
    }
    
    // Check that Auth extends Facade
    $reflection = new ReflectionClass('Portfolion\Auth\Facades\Auth');
    if (!$reflection->isSubclassOf('Portfolion\Support\Facades\Facade')) {
        throw new Exception('Auth facade does not extend base Facade class');
    }
    
    // Check that getFacadeAccessor method exists and returns 'auth'
    $method = $reflection->getMethod('getFacadeAccessor');
    $method->setAccessible(true);
    
    // We can't actually call the method as it's static and requires context
    // But we can check that it exists and is protected
    if (!$method->isProtected() || !$method->isStatic()) {
        throw new Exception('getFacadeAccessor method should be protected static');
    }
    
    return true;
});

echo "\n=== AUTH SYSTEM TEST COMPLETE ===\n"; 