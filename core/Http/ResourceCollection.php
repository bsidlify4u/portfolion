<?php

namespace Portfolion\Http;

use Countable;
use IteratorAggregate;
use ArrayIterator;
use JsonSerializable;

/**
 * Class for handling collections of API resources
 */
class ResourceCollection implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * The resource that should be transformed
     * 
     * @var mixed
     */
    protected $resource;
    
    /**
     * The resource class to instantiate
     * 
     * @var string
     */
    protected string $resourceClass;
    
    /**
     * The paginator instance, if this collection is paginated
     * 
     * @var \Portfolion\Pagination\Paginator|null
     */
    protected $paginator = null;
    
    /**
     * Additional data to be included with the resource collection
     * 
     * @var array
     */
    protected array $additional = [];
    
    /**
     * Create a new resource collection
     * 
     * @param mixed $resource
     * @param string $resourceClass
     */
    public function __construct($resource, string $resourceClass)
    {
        $this->resource = $resource;
        $this->resourceClass = $resourceClass;
        
        // Check if the resource is paginated
        if (is_object($resource) && method_exists($resource, 'items') && method_exists($resource, 'paginate')) {
            $this->paginator = $resource;
        }
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
     * Map collection items to resource instances
     * 
     * @return array
     */
    protected function collectResource()
    {
        $items = $this->paginator ? $this->paginator->items() : $this->resource;
        
        return array_map(function ($item) {
            return new $this->resourceClass($item);
        }, is_array($items) ? $items : iterator_to_array($items));
    }
    
    /**
     * Transform the resource collection into an array
     * 
     * @return array
     */
    public function toArray(): array
    {
        $result = [
            'data' => $this->collectResource(),
        ];
        
        // Add pagination data if available
        if ($this->paginator) {
            $result['meta'] = [
                'current_page' => $this->paginator->currentPage(),
                'last_page' => $this->paginator->lastPage(),
                'per_page' => $this->paginator->perPage(),
                'total' => $this->paginator->total(),
            ];
            
            $result['links'] = [
                'first' => $this->paginator->url(1),
                'last' => $this->paginator->url($this->paginator->lastPage()),
                'prev' => $this->paginator->previousPageUrl(),
                'next' => $this->paginator->nextPageUrl(),
            ];
        }
        
        return $result;
    }
    
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
    
    /**
     * Get an iterator for the resource collection
     * 
     * @return \ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->collectResource());
    }
    
    /**
     * Count the number of items in the resource collection
     * 
     * @return int
     */
    public function count(): int
    {
        return count($this->collectResource());
    }
} 