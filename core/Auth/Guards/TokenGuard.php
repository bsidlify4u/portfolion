<?php

namespace Portfolion\Auth\Guards;

use Portfolion\Auth\Authenticatable;
use Portfolion\Auth\Providers\ProviderInterface;
use Portfolion\Http\Request;

class TokenGuard implements GuardInterface
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
     * The request instance.
     *
     * @var Request
     */
    protected Request $request;

    /**
     * The token retrieval method.
     *
     * @var string
     */
    protected string $inputKey = 'api_token';

    /**
     * The token storage field.
     *
     * @var string
     */
    protected string $storageKey = 'api_token';

    /**
     * The currently authenticated user.
     *
     * @var Authenticatable|null
     */
    protected ?Authenticatable $user = null;

    /**
     * Create a new token guard instance.
     *
     * @param string $name
     * @param ProviderInterface $provider
     * @param Request $request
     * @param array $config
     */
    public function __construct(string $name, ProviderInterface $provider, Request $request, array $config = [])
    {
        $this->name = $name;
        $this->provider = $provider;
        $this->request = $request;
        $this->inputKey = $config['input_key'] ?? 'api_token';
        $this->storageKey = $config['storage_key'] ?? 'api_token';
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

        $token = $this->getTokenForRequest();

        if ($token === null) {
            return null;
        }

        $user = $this->provider->retrieveByCredentials([
            $this->storageKey => $token,
        ]);

        if ($user !== null) {
            $this->user = $user;
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
        $token = $credentials[$this->inputKey] ?? null;

        if ($token === null) {
            return false;
        }

        $user = $this->provider->retrieveByCredentials([
            $this->storageKey => $token,
        ]);

        return $user !== null;
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
            $this->setUser($user);
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
        return $this->validate($credentials);
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
        $this->user = null;
    }

    /**
     * Get the token for the current request.
     *
     * @return string|null
     */
    public function getTokenForRequest(): ?string
    {
        $token = $this->request->query($this->inputKey);

        if ($token === null) {
            $token = $this->request->input($this->inputKey);
        }

        if ($token === null) {
            $token = $this->request->bearerToken();
        }

        if ($token === null) {
            $token = $this->request->getPassword();
        }

        return $token;
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

        return $this;
    }

    /**
     * Set the token input key.
     *
     * @param string $key
     * @return $this
     */
    public function setInputKey(string $key): self
    {
        $this->inputKey = $key;

        return $this;
    }

    /**
     * Set the token storage key.
     *
     * @param string $key
     * @return $this
     */
    public function setStorageKey(string $key): self
    {
        $this->storageKey = $key;

        return $this;
    }
} 