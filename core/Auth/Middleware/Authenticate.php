<?php

namespace Portfolion\Auth\Middleware;

use Closure;
use Portfolion\Auth\AuthManager;
use Portfolion\Http\Request;
use Portfolion\Http\Response;

class Authenticate
{
    /**
     * The authentication manager instance.
     *
     * @var AuthManager
     */
    protected AuthManager $auth;

    /**
     * Create a new middleware instance.
     *
     * @param AuthManager $auth
     * @return void
     */
    public function __construct(AuthManager $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string|null ...$guards
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ?string ...$guards): mixed
    {
        $this->authenticate($request, $guards);

        return $next($request);
    }

    /**
     * Determine if the user is logged in to any of the given guards.
     *
     * @param Request $request
     * @param array $guards
     * @return void
     *
     * @throws \Exception
     */
    protected function authenticate(Request $request, array $guards): void
    {
        if (empty($guards)) {
            $guards = [null];
        }

        foreach ($guards as $guard) {
            if ($this->auth->guard($guard)->check()) {
                $this->auth->shouldUse($guard);
                return;
            }
        }

        $this->unauthenticated($request, $guards);
    }

    /**
     * Handle an unauthenticated user.
     *
     * @param Request $request
     * @param array $guards
     * @return void
     *
     * @throws \Exception
     */
    protected function unauthenticated(Request $request, array $guards): void
    {
        if ($request->expectsJson()) {
            abort(401, 'Unauthenticated.');
        }

        redirect('login')->with('error', 'You must be logged in to access this page.');
    }
} 