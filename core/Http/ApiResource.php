<?php

namespace Portfolion\Http;

use JsonSerializable;

/**
 * Base class for API resource transformers
 */
abstract class ApiResource implements JsonSerializable
{
    /**
     * The model or data that should be transformed
     * 
     * @var mixed
     */
    protected $resource;
    
    /**
     * Additional data to be included with the resource
     * 
     * @var array
     */
    protected array $additional = [];
    
    /**
     * Create a new resource instance
     * 
     * @param mixed $resource
     */
    public function __construct($resource)
    {
        $this->resource = $resource;
    }
    
    /**
     * Create a new anonymous resource collection
     * 
     * @param mixed $resource
     * @return \Portfolion\Http\ResourceCollection
     */
    public static function collection($resource)
    {
        return new ResourceCollection($resource, static::class);
    }
    
    /**
     * Add additional metadata to the resource response
     * 
     * @param array $data
     * @return $this
     */
    public function additional(array $data)
    {
        $this->additional = array_merge($this->additional, $data);
        
        return $this;
    }
    
    /**
     * Transform the resource into an array
     * 
     * @return array
     */
    abstract public function toArray(): array;
    
    /**
     * Get any additional data that should be included with the resource
     * 
     * @return array
     */
    public function with(): array
    {
        return $this->additional;
    }
    
    /**
     * Convert the object into something JSON serializable
     * 
     * @return array
     */
    public function jsonSerialize(): array
    {
        $data = $this->toArray();
        
        $with = $this->with();
        
        if (count($with) > 0) {
            return array_merge($data, $with);
        }
        
        return $data;
    }
    
    /**
     * Create an HTTP response that represents the object
     * 
     * @param \Portfolion\Http\Request $request
     * @return \Portfolion\Http\Response
     */
    public function toResponse(Request $request): Response
    {
        return (new Response)->json(
            $this->jsonSerialize(),
            200,
            ['Content-Type' => 'application/json']
        );
    }
} 