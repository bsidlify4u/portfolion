<?php

namespace Portfolion\Auth\Guards;

use Portfolion\Auth\Authenticatable;
use Portfolion\Auth\Providers\ProviderInterface;
use Portfolion\Session\SessionManager;
use Portfolion\Support\Str;

class SessionGuard implements GuardInterface
{
    /**
     * The name of the guard.
     *
     * @var string
     */
    protected string $name;

    /**
     * The user provider implementation.
     *
     * @var ProviderInterface
     */
    protected ProviderInterface $provider;

    /**
     * The session manager instance.
     *
     * @var SessionManager
     */
    protected SessionManager $session;

    /**
     * The currently authenticated user.
     *
     * @var Authenticatable|null
     */
    protected ?Authenticatable $user = null;

    /**
     * Indicates if the user was authenticated via a recaller cookie.
     *
     * @var bool
     */
    protected bool $viaRemember = false;

    /**
     * Create a new session guard instance.
     *
     * @param string $name
     * @param ProviderInterface $provider
     * @param SessionManager $session
     */
    public function __construct(string $name, ProviderInterface $provider, SessionManager $session)
    {
        $this->name = $name;
        $this->provider = $provider;
        $this->session = $session;
    }

    /**
     * Determine if the current user is authenticated.
     *
     * @return bool
     */
    public function check(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Determine if the current user is a guest.
     *
     * @return bool
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * Get the currently authenticated user.
     *
     * @return Authenticatable|null
     */
    public function user(): ?Authenticatable
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $id = $this->session->get($this->getName());

        if ($id !== null) {
            $this->user = $this->provider->retrieveById($id);
        }

        // If the user is null, but we retrieve a recaller cookie, we can attempt to
        // pull the user data on that cookie which serves as a remember "token".
        // If it's valid, we'll update the session with the user identifier.
        if ($this->user === null && $recaller = $this->getRecaller()) {
            $this->user = $this->getUserByRecaller($recaller);

            if ($this->user !== null) {
                $this->updateSession($this->user->getAuthIdentifier());
                $this->viaRemember = true;
            }
        }

        return $this->user;
    }

    /**
     * Get the ID for the currently authenticated user.
     *
     * @return int|string|null
     */
    public function id(): int|string|null
    {
        $user = $this->user();

        return $user !== null ? $user->getAuthIdentifier() : null;
    }

    /**
     * Validate a user's credentials.
     *
     * @param array $credentials
     * @return bool
     */
    public function validate(array $credentials = []): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        if ($user === null) {
            return false;
        }

        return $this->provider->validateCredentials($user, $credentials);
    }

    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param array $credentials
     * @param bool $remember
     * @return bool
     */
    public function attempt(array $credentials = [], bool $remember = false): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        if ($user === null) {
            return false;
        }

        $valid = $this->provider->validateCredentials($user, $credentials);

        if ($valid) {
            $this->login($user, $remember);
            return true;
        }

        return false;
    }

    /**
     * Log a user into the application without sessions or cookies.
     *
     * @param array $credentials
     * @return bool
     */
    public function once(array $credentials = []): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        if ($user === null) {
            return false;
        }

        $valid = $this->provider->validateCredentials($user, $credentials);

        if ($valid) {
            $this->setUser($user);
            return true;
        }

        return false;
    }

    /**
     * Log a user into the application.
     *
     * @param Authenticatable $user
     * @param bool $remember
     * @return void
     */
    public function login(Authenticatable $user, bool $remember = false): void
    {
        $this->updateSession($user->getAuthIdentifier());

        if ($remember) {
            $this->createRememberTokenIfDoesntExist($user);
            $this->queueRecallerCookie($user);
        }

        $this->setUser($user);
    }

    /**
     * Log the given user ID into the application.
     *
     * @param int|string $id
     * @param bool $remember
     * @return Authenticatable|bool
     */
    public function loginUsingId(int|string $id, bool $remember = false): Authenticatable|bool
    {
        $user = $this->provider->retrieveById($id);

        if ($user === null) {
            return false;
        }

        $this->login($user, $remember);

        return $user;
    }

    /**
     * Log the user out of the application.
     *
     * @return void
     */
    public function logout(): void
    {
        if ($this->user) {
            $this->clearUserDataFromStorage();
            $this->user = null;
            $this->viaRemember = false;
        }
    }

    /**
     * Get a unique identifier for the auth session value.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'login_' . $this->name . '_' . sha1(static::class);
    }

    /**
     * Get the name of the cookie used to store the "recaller".
     *
     * @return string
     */
    public function getRecallerName(): string
    {
        return 'remember_' . $this->name . '_' . sha1(static::class);
    }

    /**
     * Get the cookie creator instance used by the guard.
     *
     * @return mixed
     */
    protected function getCookieJar(): mixed
    {
        return app('cookie');
    }

    /**
     * Get the "recaller" cookie for the guard.
     *
     * @return string|null
     */
    protected function getRecaller(): ?string
    {
        return $this->getCookieJar()->get($this->getRecallerName());
    }

    /**
     * Pull a user from the repository by its "remember me" cookie token.
     *
     * @param string $recaller
     * @return Authenticatable|null
     */
    protected function getUserByRecaller(string $recaller): ?Authenticatable
    {
        if (!is_string($recaller) || !str_contains($recaller, '|')) {
            return null;
        }

        list($id, $token) = explode('|', $recaller, 2);

        return $this->provider->retrieveByToken($id, $token);
    }

    /**
     * Create a "remember me" cookie for a given ID.
     *
     * @param Authenticatable $user
     * @return void
     */
    protected function createRememberTokenIfDoesntExist(Authenticatable $user): void
    {
        if (empty($user->getRememberToken())) {
            $this->refreshRememberToken($user);
        }
    }

    /**
     * Refresh the "remember me" token for the user.
     *
     * @param Authenticatable $user
     * @return void
     */
    protected function refreshRememberToken(Authenticatable $user): void
    {
        $token = Str::random(60);

        $user->setRememberToken($token);

        $this->provider->updateRememberToken($user, $token);
    }

    /**
     * Queue the recaller cookie into the cookie jar.
     *
     * @param Authenticatable $user
     * @return void
     */
    protected function queueRecallerCookie(Authenticatable $user): void
    {
        $value = $user->getAuthIdentifier() . '|' . $user->getRememberToken();

        $this->getCookieJar()->forever($this->getRecallerName(), $value);
    }

    /**
     * Update the session with the given ID.
     *
     * @param string|int $id
     * @return void
     */
    protected function updateSession(string|int $id): void
    {
        $this->session->put($this->getName(), $id);
    }

    /**
     * Remove the user data from the session and cookies.
     *
     * @return void
     */
    protected function clearUserDataFromStorage(): void
    {
        $this->session->forget($this->getName());
        $this->getCookieJar()->forget($this->getRecallerName());
    }

    /**
     * Set the current user.
     *
     * @param Authenticatable $user
     * @return $this
     */
    public function setUser(Authenticatable $user): self
    {
        $this->user = $user;
        $this->viaRemember = false;

        return $this;
    }

    /**
     * Determine if the user was authenticated via "remember me" cookie.
     *
     * @return bool
     */
    public function viaRemember(): bool
    {
        return $this->viaRemember;
    }
} 