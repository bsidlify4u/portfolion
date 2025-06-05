<?php
namespace Portfolion\Database;

use InvalidArgumentException;
use RuntimeException;

/**
 * Base model class with ActiveRecord pattern implementation.
 * 
 * @template TModelClass
 * @template-covariant TThis of self
 * @template-implements HasAttributes<TModelClass>
 */
abstract class Model {
    protected ?string $table = null;
    protected string $primaryKey = 'id';
    protected array $guarded = [];
    protected array $fillable = [];
    protected array $hidden = [];
    protected array $casts = [];
    protected array $attributes = [];
    protected array $original = [];
    protected QueryBuilder $db;
    
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = []) {
        // We'll lazy-load the QueryBuilder when needed
        // instead of creating it here to prevent potential circular dependencies
        $this->fill($attributes);
    }
    
    /**
     * Find a model by its primary key.
     * 
     * @param int|string $id
     * @return static|null
     * @throws InvalidArgumentException
     */
    public static function find(int|string $id): ?static {
        $model = static::query()
            ->where(static::getPrimaryKey(), '=', $id)
            ->first();
        
        if ($model === null) {
            return null;
        }
        
        return static::newInstance((array)$model);
    }
    
    /**
     * Get all models from the database.
     * 
     * @return array<int, static>
     */
    public static function all(): array {
        $results = static::query()->get();
        return array_map(
            static fn($result) => static::newInstance((array)$result),
            $results
        );
    }
    
    /**
     * Create a new model instance in the database.
     * 
     * @param array<string, mixed> $attributes
     * @return static
     * @throws RuntimeException
     */
    public static function create(array $attributes): static {
        $model = static::newInstance($attributes);
        
        if (!$model->save()) {
            throw new RuntimeException(sprintf(
                'Failed to create model %s with attributes: %s',
                static::class,
                json_encode($attributes, JSON_THROW_ON_ERROR)
            ));
        }
        
        return $model;
    }
    
    /**
     * Get a new query builder instance for this model.
     * 
     * @return QueryBuilder
     */
    public static function query(): QueryBuilder {
        /** @var static<TModelClass, TThis> $instance */
        $instance = static::newInstance();
        return $instance->newQuery();
    }
     /**
     * Create a new instance of the model.
     * 
     * @param array<string, mixed> $attributes
     * @return static
     */
    protected static function newInstance(array $attributes = []): static {
        return new static($attributes);
    }

    /**
     * Get a new query builder for this model.
     * 
     * @return QueryBuilder
     */
    protected function newQuery(): QueryBuilder {
        if (!isset($this->db)) {
            $this->db = new QueryBuilder();
        }
        return $this->db->table($this->getTable());
    }
    
    /**
     * Get the primary key for the model.
     * 
     * @return string
     */
    protected static function getPrimaryKey(): string {
        return static::newInstance()->primaryKey;
    }
    
    /**
     * Update the model's attributes.
     * 
     * @param array<string, mixed> $attributes
     * @return bool
     */
    public function update(array $attributes = []): bool {
        $this->fill($attributes);
        return $this->save();
    }
    
    /**
     * Delete the model from the database.
     * 
     * @return bool
     */
    public function delete(): bool {
        return (bool)$this->newQuery()
            ->where($this->primaryKey, '=', $this->getAttribute($this->primaryKey))
            ->delete();
    }
    
    /**
     * Fill the model with attributes.
     * 
     * @param array<string, mixed> $attributes
     */
    public function fill(array $attributes): void {
        // Always set the primary key if it exists in the attributes
        if (isset($attributes[$this->primaryKey])) {
            $this->setAttribute($this->primaryKey, $attributes[$this->primaryKey]);
        }
        
        foreach ($attributes as $key => $value) {
            // Skip the primary key since we already handled it
            if ($key === $this->primaryKey) {
                continue;
            }
            
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $this->castAttribute($key, $value));
            }
        }
    }
    
    /**
     * Save the model to the database.
     * 
     * @return bool
     */
    public function save(): bool {
        $attributes = [];
        
        // Process and format all attributes
        foreach ($this->attributes as $key => $value) {
            // Handle DateTime objects and casts
            if ($value instanceof \DateTime) {
                if (isset($this->casts[$key]) && $this->casts[$key] === 'date') {
                    $attributes[$key] = $value->format('Y-m-d');
                } else {
                    $attributes[$key] = $value->format('Y-m-d H:i:s');
                }
                error_log("Formatted DateTime object $key to: {$attributes[$key]}");
            } elseif (isset($this->casts[$key]) && in_array($this->casts[$key], ['date', 'datetime']) && is_string($value)) {
                try {
                    $dateTime = new \DateTime($value);
                    if ($this->casts[$key] === 'date') {
                        $attributes[$key] = $dateTime->format('Y-m-d');
                    } else {
                        $attributes[$key] = $dateTime->format('Y-m-d H:i:s');
                    }
                    error_log("Parsed and formatted $key to: {$attributes[$key]}");
                } catch (\Exception $e) {
                    // Keep the original value if it can't be parsed
                    $attributes[$key] = $value;
                    error_log("Failed to parse $key: {$e->getMessage()}");
                }
            } else {
                // For non-date attributes, just copy the value
                $attributes[$key] = $value;
            }
        }
        
        error_log("Final attributes for save: " . print_r($attributes, true));
        
        if (isset($this->attributes[$this->primaryKey])) {
            error_log("Updating record with ID: {$this->attributes[$this->primaryKey]}");
            $saved = $this->newQuery()
                ->where($this->primaryKey, '=', $this->getAttribute($this->primaryKey))
                ->update($attributes);
            
            error_log("Update result: " . ($saved ? 'true' : 'false'));
            
            if ($saved) {
                $this->syncOriginal();
            }
            return (bool)$saved;
        }
        
        error_log("Inserting new record");
        $id = $this->newQuery()->insertGetId($attributes);
        if (!is_int($id)) {
            error_log("Insert failed, no ID returned");
            return false;
        }
        
        error_log("Insert successful, ID: $id");
        $this->setAttribute($this->primaryKey, $id);
        $this->syncOriginal();
        
        return true;
    }
    
    /**
     * Get an attribute value.
     * 
     * @param string $key
     * @return mixed
     */
    public function getAttribute(string $key): mixed {
        return $this->attributes[$key] ?? null;
    }
    
    /**
     * Set an attribute value.
     * 
     * @param string $key
     * @param mixed $value
     */
    public function setAttribute(string $key, mixed $value): void {
        $this->attributes[$key] = $value;
    }
    
    /**
     * Get all attributes.
     * 
     * @return array<string, mixed>
     */
    public function getAttributes(): array {
        return $this->attributes;
    }
    
    /**
     * Get the table name.
     */
    protected function getTable(): string {
        if ($this->table === null) {
            $name = class_basename(static::class);
            $name = preg_replace('/(?<!^)[A-Z]/', '_$0', $name);
            $this->table = strtolower($name) . 's';
        }
        
        return $this->table;
    }
    
    /**
     * Determine if an attribute can be filled.
     */
    protected function isFillable(string $key): bool {
        if (in_array($key, $this->guarded, true)) {
            return false;
        }
        
        return empty($this->fillable) || in_array($key, $this->fillable, true);
    }
    
    /**
     * Cast an attribute to a native PHP type.
     * 
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    protected function castAttribute(string $key, mixed $value): mixed {
        if (!isset($this->casts[$key]) || $value === null) {
            return $value;
        }
        
        // If it's already a DateTime object, return it as is
        if ($value instanceof \DateTime && in_array($this->casts[$key], ['date', 'datetime'])) {
            return $value;
        }
        
        return match ($this->casts[$key]) {
            'int', 'integer' => (int) $value,
            'real', 'float', 'double' => (float) $value,
            'string' => (string) $value,
            'bool', 'boolean' => (bool) $value,
            'array', 'json' => is_string($value) ? json_decode($value, true) : (array) $value,
            'date', 'datetime' => $this->asDateTime($value),
            default => $value,
        };
    }
    
    /**
     * Convert a value to a DateTime instance.
     *
     * @param mixed $value
     * @return \DateTime
     */
    protected function asDateTime(mixed $value): \DateTime {
        if ($value instanceof \DateTime) {
            return $value;
        }
        
        if (is_numeric($value)) {
            return new \DateTime('@' . $value);
        }
        
        if (is_string($value)) {
            return new \DateTime($value);
        }
        
        return new \DateTime();
    }
    
    /**
     * Sync the original attributes with the current.
     */
    protected function syncOriginal(): void {
        $this->original = $this->attributes;
    }
    
    /**
     * Dynamically retrieve attributes.
     * 
     * @param string $key
     * @return mixed
     */
    public function __get(string $key): mixed {
        return $this->getAttribute($key);
    }
    
    /**
     * Dynamically set attributes.
     * 
     * @param string $key
     * @param mixed $value
     */
    public function __set(string $key, mixed $value): void {
        $this->setAttribute($key, $value);
    }
    
    /**
     * Determine if an attribute exists.
     * 
     * @param string $key
     * @return bool
     */
    public function __isset(string $key): bool {
        return isset($this->attributes[$key]);
    }
    
    /**
     * Convert the model to an array.
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array {
        $array = [];
        
        // Always include the primary key
        if (isset($this->attributes[$this->primaryKey])) {
            $array[$this->primaryKey] = $this->attributes[$this->primaryKey];
        }
        
        foreach ($this->attributes as $key => $value) {
            if (!in_array($key, $this->hidden, true) && $key !== $this->primaryKey) {
                $array[$key] = $value instanceof self ? $value->toArray() : $value;
            }
        }
        
        // Always include timestamps
        if (!isset($array['created_at'])) {
            $array['created_at'] = date('Y-m-d H:i:s');
        }
        
        if (!isset($array['updated_at'])) {
            $array['updated_at'] = date('Y-m-d H:i:s');
        }
        
        return $array;
    }

    /**
     * Convert the model to an array for Twig.
     * 
     * @return array<string, mixed>
     */
    public function toTwig(): array {
        return $this->toArray();
    }
}
