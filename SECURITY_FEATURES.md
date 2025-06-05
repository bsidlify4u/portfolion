# Portfolion Framework Security Features

## Overview

Portfolion includes several built-in security features to protect your application from common threats. This document outlines these features and best practices for using them.

## Rate Limiting

Rate limiting helps protect your API endpoints from abuse, brute force attacks, and denial of service.

### Configuration

The framework provides a flexible `RateLimiter` middleware with multiple configuration options:

```php
// config/api.php
'rate_limiting' => [
    'enabled' => env('API_RATE_LIMITING', true),
    
    'limiters' => [
        'default' => [
            'max_attempts' => 60,  // 60 requests
            'decay_minutes' => 1,  // per minute
            'by' => 'ip',          // based on IP address
        ],
        
        'auth' => [
            'max_attempts' => 5,   // 5 attempts
            'decay_minutes' => 10, // per 10 minutes
            'by' => 'ip',          // based on IP address
            'response_message' => 'Too many login attempts. Please try again later.',
        ],
    ],
],
```

### Implementation

Apply rate limiting to specific routes or route groups:

```php
// Apply default rate limiter
$router->get('/api/data', ['middleware' => 'rate_limit', 'uses' => 'DataController@index']);

// Apply a specific rate limiter
$router->post('/auth/login', ['middleware' => 'rate_limit:auth', 'uses' => 'AuthController@login']);

// Apply to route groups
$router->group(['middleware' => 'rate_limit:api_key'], function ($router) {
    $router->get('/api/sensitive-data', 'DataController@sensitive');
});
```

### Rate Limiting Options

The rate limiter can be configured by different criteria:

- `ip`: Limit by client IP address
- `user`: Limit by authenticated user ID
- `api_key`: Limit by API key
- `route`: Limit by specific route
- Custom: Implement your own criteria

## Secure Logging

The framework's logging system includes specialized channels for security-related events.

### Security Logging Channel

```php
// In your controllers or services
app()->get('log')->channel('security')->warning('Failed login attempt', [
    'ip' => $request->ip(),
    'username' => $request->input('username'),
    'user_agent' => $request->userAgent()
]);
```

### Audit Logging Channel

For tracking sensitive operations and changes:

```php
app()->get('log')->channel('audit')->info('User data modified', [
    'user_id' => auth()->user()->getId(),
    'field' => 'email',
    'old_value' => $user->getOriginal('email'),
    'new_value' => $user->email,
    'ip' => $request->ip()
]);
```

### Log Security Configuration

The security logs use specialized configuration:

```php
// config/logging.php
'security' => [
    'driver' => 'daily',
    'path' => storage_path('logs/security.log'),
    'level' => 'info',
    'days' => 90,      // Retain logs for 90 days
    'permission' => 0600,  // Restricted file permissions
],
```

## Input Validation

Portfolion includes a robust validation system to help prevent injection attacks and data integrity issues.

### Request Validation

```php
class UserRequest extends Request
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
        ];
    }
}

class UserController extends Controller
{
    public function store(UserRequest $request)
    {
        // Input is already validated
        $user = User::create($request->validated());
        return redirect()->with('success', 'User created successfully');
    }
}
```

## Cross-Site Request Forgery (CSRF) Protection

The framework automatically includes CSRF protection for web routes.

### Configuration

```php
// config/app.php
'csrf' => [
    'enabled' => true,
    'token_lifetime' => 120, // minutes
    'same_site' => 'lax',
],
```

### CSRF Token Field

Include in your forms:

```html
<form method="POST" action="/profile">
    <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
    <!-- Form fields -->
</form>
```

### CSRF Token Verification

The framework automatically validates CSRF tokens for POST, PUT, PATCH, and DELETE requests to web routes.

## Cross-Origin Resource Sharing (CORS)

Configure CORS for API endpoints:

```php
// config/api.php
'cors' => [
    'enabled' => env('API_CORS_ENABLED', true),
    'allowed_origins' => explode(',', env('API_CORS_ORIGINS', '*')),
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'Authorization', 'X-API-Key'],
    'exposed_headers' => ['X-RateLimit-Limit', 'X-RateLimit-Remaining', 'X-RateLimit-Reset'],
    'max_age' => 86400, // 24 hours
    'allow_credentials' => true,
],
```

## Secure Headers

Portfolion can automatically add security headers to responses.

### Configuration

```php
// config/security.php
'headers' => [
    'X-Content-Type-Options' => 'nosniff',
    'X-Frame-Options' => 'SAMEORIGIN',
    'X-XSS-Protection' => '1; mode=block',
    'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
    'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'",
    'Referrer-Policy' => 'same-origin',
    'Feature-Policy' => "geolocation 'self'; microphone 'none'; camera 'none'",
],
```

## Session Security

The framework includes secure session handling with multiple drivers and security options.

### Configuration

```php
// config/session.php
return [
    'driver' => env('SESSION_DRIVER', 'file'),
    'lifetime' => env('SESSION_LIFETIME', 120), // minutes
    'secure' => env('SESSION_SECURE_COOKIE', true), // HTTPS only
    'http_only' => true, // Not accessible via JavaScript
    'same_site' => 'lax', // Prevents CSRF with minimal disruption
];
```

## Best Practices

1. **Always validate user input** using the framework's validation system
2. **Use HTTPS in production** and configure secure cookies
3. **Implement rate limiting** on login pages and API endpoints
4. **Log security events** using the dedicated security channel
5. **Update the framework regularly** to get the latest security patches
6. **Use prepared statements** for database queries (built into the QueryBuilder)
7. **Sanitize output** when displaying user-provided content
8. **Set up proper file permissions** for sensitive files like .env and logs
9. **Use strong password hashing** with the Auth system
10. **Implement proper authentication** for all sensitive operations 