<?php
namespace Portfolion\Events;

use Portfolion\Queue\QueueManager;

class EventDispatcher {
    private static $instance = null;
    private array $listeners = [];
    private QueueManager $queue;
    
    private function __construct() {
        $this->queue = new QueueManager();
    }
    
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function subscribe(string $event, callable $listener): void {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }
        $this->listeners[$event][] = $listener;
    }
    
    public function dispatch(Event $event): void {
        $eventClass = get_class($event);
        
        if (!isset($this->listeners[$eventClass])) {
            return;
        }
        
        foreach ($this->listeners[$eventClass] as $listener) {
            if ($event->shouldQueue) {
                $this->queue->push([
                    'listener' => serialize($listener),
                    'event' => serialize($event)
                ]);
            } else {
                $listener($event);
            }
        }
    }
    
    public function processQueuedEvents(): void {
        while ($job = $this->queue->pop()) {
            $listener = unserialize($job['listener']);
            $event = unserialize($job['event']);
            
            try {
                $listener($event);
                $this->queue->delete($job);
            } catch (\Throwable $e) {
                $this->queue->release($job);
                error_log($e->getMessage());
            }
        }
    }
    
    public function unsubscribe(string $event, callable $listener): void {
        if (!isset($this->listeners[$event])) {
            return;
        }
        
        $this->listeners[$event] = array_filter(
            $this->listeners[$event],
            function ($existingListener) use ($listener) {
                return $existingListener !== $listener;
            }
        );
    }
    
    public function hasListeners(string $event): bool {
        return isset($this->listeners[$event]) && !empty($this->listeners[$event]);
    }
    
    public function getListeners(string $event): array {
        return $this->listeners[$event] ?? [];
    }
}
