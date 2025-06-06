<?php

namespace Portfolion\View;

interface ViewEngineInterface
{
    /**
     * Render a view
     *
     * @param string $view View name
     * @param array $data View data
     * @return string Rendered view
     */
    public function render(string $view, array $data = []): string;
    
    /**
     * Check if a view exists
     *
     * @param string $view View name
     * @return bool
     */
    public function exists(string $view): bool;
    
    /**
     * Share data with all views
     *
     * @param string $key Data key
     * @param mixed $value Data value
     * @return void
     */
    public function share(string $key, $value): void;
    
    /**
     * Add a view path
     *
     * @param string $path Path to views
     * @return void
     */
    public function addPath(string $path): void;
} 