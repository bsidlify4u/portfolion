<?php

namespace App\Models;

use Portfolion\Database\Model;

class Post extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'posts';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'content',
        'slug',
        'user_id',
        'published_at',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'published_at' => 'datetime',
        'is_featured' => 'boolean',
    ];
    
    /**
     * Get the user that owns the post.
     *
     * @return \Portfolion\Database\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get all comments for the post.
     *
     * @return \Portfolion\Database\Relations\HasMany
     */
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
    
    /**
     * Get all tags for the post.
     *
     * @return \Portfolion\Database\Relations\BelongsToMany
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }
    
    /**
     * Scope a query to only include published posts.
     *
     * @param \Portfolion\Database\Query\Builder $query
     * @return \Portfolion\Database\Query\Builder
     */
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
                     ->where('published_at', '<=', now());
    }
    
    /**
     * Scope a query to only include featured posts.
     *
     * @param \Portfolion\Database\Query\Builder $query
     * @return \Portfolion\Database\Query\Builder
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
    
    /**
     * Check if the post is published.
     *
     * @return bool
     */
    public function isPublished()
    {
        return $this->published_at !== null && $this->published_at <= now();
    }
    
    /**
     * Get the URL for the post.
     *
     * @return string
     */
    public function getUrl()
    {
        return "/posts/{$this->slug}";
    }
} 