<?php

namespace Portfolion\Auth\Providers;

use Portfolion\Auth\Authenticatable;
use Portfolion\Database\DB;
use Portfolion\Auth\User;
use Portfolion\Hash\HashManager;

class UserProvider implements ProviderInterface
{
    /**
     * The user model class name.
     *
     * @var string
     */
    protected string $model;

    /**
     * The database connection.
     *
     * @var string|null
     */
    protected ?string $connection;

    /**
     * The hash manager instance.
     *
     * @var HashManager
     */
    protected HashManager $hasher;

    /**
     * Create a new database user provider.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->model = $config['model'] ?? User::class;
        $this->connection = $config['connection'] ?? null;
        $this->hasher = app(HashManager::class);
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param mixed $identifier
     * @return Authenticatable|null
     */
    public function retrieveById(mixed $identifier): ?Authenticatable
    {
        $model = $this->createModel();
        
        return $model->find($identifier);
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param array $credentials
     * @return Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (empty($credentials) || (count($credentials) === 1 && array_key_exists('password', $credentials))) {
            return null;
        }

        // First we will add each credential element to the query as a where clause.
        // Then we can execute the query and, if we found a user, return it in a
        // Eloquent User "model" that will be utilized by the Guard instances.
        $query = $this->newModelQuery();

        foreach ($credentials as $key => $value) {
            if ($key === 'password') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->first();
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param Authenticatable $user
     * @param array $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        // Check if the required password credential is provided
        if (!isset($credentials['password'])) {
            return false;
        }

        $plain = $credentials['password'];

        return $this->hasher->check($plain, $user->getAuthPassword());
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param mixed $identifier
     * @param string $token
     * @return Authenticatable|null
     */
    public function retrieveByToken(mixed $identifier, string $token): ?Authenticatable
    {
        $model = $this->createModel();

        $retrievedModel = $model->where($model->getAuthIdentifierName(), $identifier)
            ->where($model->getRememberTokenName(), $token)
            ->first();

        return $retrievedModel;
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param Authenticatable $user
     * @param string $token
     * @return void
     */
    public function updateRememberToken(Authenticatable $user, string $token): void
    {
        $user->setRememberToken($token);
        
        if (method_exists($user, 'save')) {
            $user->save();
        } else {
            // If the user model doesn't have a save method, manually update via query
            $this->newModelQuery()
                ->where($user->getAuthIdentifierName(), $user->getAuthIdentifier())
                ->update([$user->getRememberTokenName() => $token]);
        }
    }

    /**
     * Create a new instance of the model.
     *
     * @return Authenticatable
     */
    public function createModel(): Authenticatable
    {
        $class = '\\' . ltrim($this->model, '\\');

        return new $class;
    }

    /**
     * Get a new query builder for the model instance.
     *
     * @param Authenticatable|null $model
     * @return \Portfolion\Database\QueryBuilder
     */
    protected function newModelQuery(?Authenticatable $model = null): \Portfolion\Database\QueryBuilder
    {
        $model = $model ?: $this->createModel();

        $table = method_exists($model, 'getTable') 
            ? $model->getTable() 
            : (property_exists($model, 'table') ? $model->table : strtolower(class_basename($model)) . 's');

        return DB::connection($this->connection)->table($table);
    }
} 