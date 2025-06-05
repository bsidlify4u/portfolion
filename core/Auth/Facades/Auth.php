<?php

namespace Portfolion\Auth\Facades;

use Portfolion\Support\Facades\Facade;

/**
 * @method static bool check()
 * @method static bool guest()
 * @method static \Portfolion\Auth\Authenticatable|null user()
 * @method static int|string|null id()
 * @method static bool validate(array $credentials = [])
 * @method static bool attempt(array $credentials = [], bool $remember = false)
 * @method static bool once(array $credentials = [])
 * @method static void login(\Portfolion\Auth\Authenticatable $user, bool $remember = false)
 * @method static \Portfolion\Auth\Authenticatable|bool loginUsingId(int|string $id, bool $remember = false)
 * @method static void logout()
 * @method static \Portfolion\Auth\Guards\GuardInterface guard(string|null $name = null)
 * @method static void shouldUse(string $name)
 * 
 * @see \Portfolion\Auth\AuthManager
 */
class Auth extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'auth';
    }
} 