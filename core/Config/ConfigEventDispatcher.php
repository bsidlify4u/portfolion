<?php

namespace Portfolion\Config;

use RuntimeException;

/**
 * Dispatches configuration events to registered listeners
 */
class ConfigEventDispatcher {
    private array $listeners = [];
    
    /**
     * Register a listener for a configuration event
     */
    public function addListener(string $event, callable $listener): void {
        $this->listeners[$event][] = $listener;
    }
    
    /**
     * Dispatch an event to all registered listeners
     */
    public function dispatch(string $event, array $payload = []): void {
        if (!isset($this->listeners[$event])) {
            return;
        }
        
        foreach ($this->listeners[$event] as $listener) {
            $listener($payload);
        }
    }
}
