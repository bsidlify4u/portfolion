<?php
namespace App\Middleware;

use Portfolion\Http\Request;
use Portfolion\Http\Response;
use Portfolion\Security\FormValidator;
use Portfolion\Database\QueryBuilder;

class RequestValidationMiddleware {
    private ?QueryBuilder $db;
    
    public function __construct(?QueryBuilder $db = null) {
        $this->db = $db;
    }
    
    public function handle(Request $request, \Closure $next): Response {
        $route = $request->getRoute();
        if ($route === null) {
            return $next($request);
        }
        
        $rules = $this->getRulesForRoute($route);
        if (empty($rules)) {
            return $next($request);
        }
        
        $validator = FormValidator::make(
            $request->all(),
            $rules,
            [],
            $this->db
        );
        
        if ($validator->fails()) {
            return (new Response())->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->getErrors()
            ], 422);
        }
        
        return $next($request);
    }
    
    /**
     * Get validation rules for the given route.
     *
     * @param string $route
     * @return array<string, array<string>>
     */
    private function getRulesForRoute(string $route): array {
        return [
            // Add your route validation rules here
            'users/create' => [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'unique:users'],
                'password' => ['required', 'string', 'min:8'],
            ],
            // ... other route rules
        ][$route] ?? [];
    }
}
