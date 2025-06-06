<?php

namespace Portfolion\View;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension to provide access to models in Twig templates.
 */
class TwigModelAccessExtension extends AbstractExtension
{
    /**
     * Returns the name of the extension.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'portfolion_model_access';
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('model', [$this, 'getModel']),
            new TwigFunction('find', [$this, 'findModel']),
            new TwigFunction('all', [$this, 'getAllModels']),
            new TwigFunction('where', [$this, 'whereModels']),
        ];
    }

    /**
     * Get a model instance.
     *
     * @param string $model Model class name
     * @return object
     */
    public function getModel(string $model): object
    {
        $className = $this->resolveModelClass($model);
        return new $className();
    }

    /**
     * Find a model by ID.
     *
     * @param string $model Model class name
     * @param mixed $id Model ID
     * @return object|null
     */
    public function findModel(string $model, $id): ?object
    {
        $className = $this->resolveModelClass($model);
        return $className::find($id);
    }

    /**
     * Get all models.
     *
     * @param string $model Model class name
     * @return array
     */
    public function getAllModels(string $model): array
    {
        $className = $this->resolveModelClass($model);
        return $className::all();
    }

    /**
     * Get models by conditions.
     *
     * @param string $model Model class name
     * @param string $column Column name
     * @param mixed $value Column value
     * @return array
     */
    public function whereModels(string $model, string $column, $value): array
    {
        $className = $this->resolveModelClass($model);
        return $className::where($column, $value)->get();
    }

    /**
     * Resolve the model class name.
     *
     * @param string $model Model name
     * @return string
     */
    private function resolveModelClass(string $model): string
    {
        // If the model already has a namespace, use it as is
        if (strpos($model, '\\') !== false) {
            return $model;
        }

        // Otherwise, assume it's in the App\Models namespace
        return 'App\\Models\\' . $model;
    }
} 