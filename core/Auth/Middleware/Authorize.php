<?php

namespace Portfolion\Auth\Middleware;

use Closure;
use Portfolion\Auth\Facades\Auth;
use Portfolion\Http\Request;
use Portfolion\Http\Response;

class Authorize
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string $ability
     * @param string|null $model
     * @return mixed
     *
     * @throws \Exception
     */
    public function handle(Request $request, Closure $next, string $ability, ?string $model = null): mixed
    {
        $this->authorize($request, $ability, $model);

        return $next($request);
    }

    /**
     * Determine if the user is authorized to perform the given ability.
     *
     * @param Request $request
     * @param string $ability
     * @param string|null $model
     * @return void
     *
     * @throws \Exception
     */
    protected function authorize(Request $request, string $ability, ?string $model): void
    {
        // If no specific model instance is provided, we will just check the general ability
        if ($model === null) {
            if (app('gate')->denies($ability)) {
                $this->unauthorized($request, $ability);
            }
            return;
        }

        // If we have a model, we need to get the instance from the request parameters
        $parameter = $request->route($model);

        if (!$parameter) {
            // If the parameter isn't found, we'll try to resolve it from the container
            try {
                $parameter = app($model);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException(
                    "Unable to resolve model [{$model}] from container or route parameters."
                );
            }
        }

        if (app('gate')->denies($ability, $parameter)) {
            $this->unauthorized($request, $ability, $model);
        }
    }

    /**
     * Handle an unauthorized request.
     *
     * @param Request $request
     * @param string $ability
     * @param string|null $model
     * @return void
     *
     * @throws \Exception
     */
    protected function unauthorized(Request $request, string $ability, ?string $model = null): void
    {
        if ($request->expectsJson()) {
            abort(403, 'This action is unauthorized.');
        }

        $message = $model
            ? "You are not authorized to {$ability} this {$model}."
            : "You are not authorized to {$ability}.";

        redirect()->back()->with('error', $message);
    }
} 