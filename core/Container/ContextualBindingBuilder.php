<?php

namespace Portfolion\Container;

use Closure;

class ContextualBindingBuilder
{
    /**
     * The container instance.
     *
     * @var Container
     */
    protected $container;

    /**
     * The concrete instance.
     *
     * @var string|array
     */
    protected $concrete;

    /**
     * The abstract target.
     *
     * @var string
     */
    protected $needs;

    /**
     * Create a new contextual binding builder.
     *
     * @param  Container  $container
     * @param  string|array  $concrete
     */
    public function __construct(Container $container, $concrete)
    {
        $this->concrete = $concrete;
        $this->container = $container;
    }

    /**
     * Define the abstract target that depends on the context.
     *
     * @param  string  $abstract
     * @return $this
     */
    public function needs(string $abstract): self
    {
        $this->needs = $abstract;

        return $this;
    }

    /**
     * Define the implementation for the contextual binding.
     *
     * @param  \Closure|string|array  $implementation
     * @return void
     */
    public function give($implementation): void
    {
        foreach (array_wrap($this->concrete) as $concrete) {
            $this->container->addContextualBinding(
                $concrete, $this->needs, $implementation
            );
        }
    }

    /**
     * Define tagged services to be used as the implementation.
     *
     * @param  string  $tag
     * @return void
     */
    public function giveTagged(string $tag): void
    {
        $this->give(function ($container) use ($tag) {
            $taggedServices = $container->tagged($tag);

            return count($taggedServices) > 0 ? $taggedServices : null;
        });
    }
}

/**
 * If not exists, create an array_wrap helper function
 */
if (!function_exists('array_wrap')) {
    /**
     * Wrap the given value in an array if it's not already an array.
     *
     * @param  mixed  $value
     * @return array
     */
    function array_wrap($value): array
    {
        if (is_null($value)) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }
} 