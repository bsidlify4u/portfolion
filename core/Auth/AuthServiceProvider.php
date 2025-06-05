<?php

namespace Portfolion\Auth;

use Portfolion\Auth\Commands\AuthMakeUserCommand;
use Portfolion\Foundation\Application;
use Portfolion\Foundation\ServiceProvider;
use Portfolion\Hash\HashManager;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->registerAuthenticator();
        $this->registerHasher();
        $this->registerUserResolver();
        $this->registerAccessGate();
        $this->registerCommands();
    }

    /**
     * Register the authenticator services.
     *
     * @return void
     */
    protected function registerAuthenticator(): void
    {
        $this->app->singleton('auth', function ($app) {
            // Once the authentication service has actually been requested by the developer
            // we will set a variable in the application indicating such. This helps us
            // know that we need to set any queued cookies in the after event later.
            $app['auth.loaded'] = true;

            return new AuthManager($app);
        });

        $this->app->singleton('auth.driver', function ($app) {
            return $app['auth']->guard();
        });
    }

    /**
     * Register the hasher services.
     *
     * @return void
     */
    protected function registerHasher(): void
    {
        $this->app->singleton('hash', function () {
            return new HashManager();
        });
    }

    /**
     * Register the user resolver.
     *
     * @return void
     */
    protected function registerUserResolver(): void
    {
        $this->app->bind('auth.user', function ($app) {
            return $app['auth']->user();
        });
    }

    /**
     * Register the access gate service.
     *
     * @return void
     */
    protected function registerAccessGate(): void
    {
        $this->app->singleton('gate', function ($app) {
            return new Gate($app, function () use ($app) {
                return call_user_func($app['auth']->userResolver());
            });
        });
    }

    /**
     * Register the auth related commands.
     *
     * @return void
     */
    protected function registerCommands(): void
    {
        $this->app->bind('command.auth.make-user', function ($app) {
            return new AuthMakeUserCommand();
        });

        $this->commands([
            'command.auth.make-user',
        ]);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            'auth', 'auth.driver', 'hash', 'auth.user', 'gate',
            'command.auth.make-user',
        ];
    }
} 