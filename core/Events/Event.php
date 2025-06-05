<?php
namespace Portfolion\Events;

abstract class Event {
    public bool $shouldQueue = false;
    
    public function __construct(array $data = []) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}
