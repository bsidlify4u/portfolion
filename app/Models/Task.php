<?php

namespace App\Models;

use Portfolion\Database\Model;
use Portfolion\Database\QueryBuilder;

class Task extends Model
{
    /**
     * The table associated with the model.
     *
     * @var ?string
     */
    protected ?string $table = 'tasks';
    
    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected string $primaryKey = 'id';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected array $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'created_at',
        'updated_at'
    ];
    
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected array $hidden = [];
    
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected array $casts = [
        'due_date' => 'date'
    ];
    
    /**
     * Get a new query builder for this model.
     * 
     * @return QueryBuilder
     */
    protected function newQuery(): QueryBuilder
    {
        // Lazy-load the QueryBuilder to prevent circular dependencies
        if (!isset($this->db)) {
            $this->db = new QueryBuilder();
        }
        
        return $this->db->table($this->getTable());
    }

    /**
     * Set the due_date attribute.
     *
     * @param string|null|\DateTime $value
     * @return void
     */
    public function setDueDateAttribute($value): void
    {
        if (empty($value)) {
            $this->attributes['due_date'] = null;
            return;
        }
        
        // If it's already a DateTime object, just use it
        if ($value instanceof \DateTime) {
            $this->attributes['due_date'] = $value;
            return;
        }
        
        try {
            $date = new \DateTime($value);
            $this->attributes['due_date'] = $date;
        } catch (\Exception $e) {
            $this->attributes['due_date'] = null;
            error_log("Failed to parse due_date: " . $e->getMessage());
        }
    }

    /**
     * Get the due_date attribute.
     *
     * @param mixed $value
     * @return string|null
     */
    public function getDueDateAttribute($value): ?string
    {
        if (empty($value)) {
            return null;
        }
        
        if ($value instanceof \DateTime) {
            return $value->format('Y-m-d');
        }
        
        try {
            $date = new \DateTime($value);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            error_log("Failed to format due_date: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get the created_at attribute.
     *
     * @param mixed $value
     * @return string|null
     */
    public function getCreatedAtAttribute($value): ?string
    {
        if (empty($value)) {
            return date('Y-m-d H:i:s');
        }
        
        if ($value instanceof \DateTime) {
            return $value->format('Y-m-d H:i:s');
        }
        
        try {
            $date = new \DateTime($value);
            return $date->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            error_log("Failed to format created_at: " . $e->getMessage());
            return date('Y-m-d H:i:s');
        }
    }

    /**
     * Get the updated_at attribute.
     *
     * @param mixed $value
     * @return string|null
     */
    public function getUpdatedAtAttribute($value): ?string
    {
        if (empty($value)) {
            return date('Y-m-d H:i:s');
        }
        
        if ($value instanceof \DateTime) {
            return $value->format('Y-m-d H:i:s');
        }
        
        try {
            $date = new \DateTime($value);
            return $date->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            error_log("Failed to format updated_at: " . $e->getMessage());
            return date('Y-m-d H:i:s');
        }
    }

    /**
     * Save the model to the database.
     *
     * @return bool
     */
    public function save(): bool
    {
        // Set timestamps
        if (!isset($this->attributes['created_at'])) {
            $this->attributes['created_at'] = date('Y-m-d H:i:s');
        }
        
        $this->attributes['updated_at'] = date('Y-m-d H:i:s');
        
        return parent::save();
    }
} 