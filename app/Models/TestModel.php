<?php

namespace App\Models;

use Portfolion\Database\Model;

class TestModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var ?string
     */
    protected ?string $table = 'test_models';
    
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
        // Define your fillable attributes here
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
    protected array $casts = [];
}