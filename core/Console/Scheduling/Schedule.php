<?php

namespace Portfolion\Console\Scheduling;

use Closure;
use DateTimeZone;
use DateTimeInterface;
use DateTimeImmutable;
use InvalidArgumentException;
use Portfolion\Config;

/**
 * Manages the scheduling of commands
 */
class Schedule
{
    /**
     * @var array<Event> All the events on the schedule
     */
    protected array $events = [];
    
    /**
     * @var string The default timezone
     */
    protected string $timezone;
    
    /**
     * Create a new schedule instance
     */
    public function __construct()
    {
        $config = Config::getInstance();
        $this->timezone = $config->get('app.timezone', 'UTC');
    }
    
    /**
     * Add a new command event to the schedule
     * 
     * @param string $command The command to schedule
     * @param array $parameters Parameters to pass to the command
     * @return Event
     */
    public function command(string $command, array $parameters = []): Event
    {
        if (!empty($parameters)) {
            $command .= ' ' . $this->compileParameters($parameters);
        }
        
        $event = new Event($command, $this->timezone);
        
        $this->events[] = $event;
        
        return $event;
    }
    
    /**
     * Add a new shell command event to the schedule
     * 
     * @param string $command The command to schedule
     * @return Event
     */
    public function exec(string $command): Event
    {
        $event = new Event($command, $this->timezone);
        
        $this->events[] = $event;
        
        return $event;
    }
    
    /**
     * Add a new callback event to the schedule
     * 
     * @param Closure $callback The callback to schedule
     * @param array $parameters Parameters to pass to the callback
     * @return CallbackEvent
     */
    public function call(Closure $callback, array $parameters = []): CallbackEvent
    {
        $event = new CallbackEvent($callback, $parameters, $this->timezone);
        
        $this->events[] = $event;
        
        return $event;
    }
    
    /**
     * Compile an array of command parameters into a command string
     * 
     * @param array $parameters
     * @return string
     */
    protected function compileParameters(array $parameters): string
    {
        $compiledParameters = [];
        
        foreach ($parameters as $key => $value) {
            // If the key is numeric, it's a value parameter (not an option)
            if (is_numeric($key)) {
                $compiledParameters[] = $this->escapeParameter($value);
                continue;
            }
            
            // Handle boolean flags
            if (is_bool($value)) {
                if ($value) {
                    $compiledParameters[] = "--{$key}";
                }
                continue;
            }
            
            // Handle regular option with value
            $compiledParameters[] = "--{$key}=" . $this->escapeParameter($value);
        }
        
        return implode(' ', $compiledParameters);
    }
    
    /**
     * Escape a parameter for the command line
     * 
     * @param mixed $value
     * @return string
     */
    protected function escapeParameter($value): string
    {
        if (is_numeric($value)) {
            return (string) $value;
        }
        
        return escapeshellarg((string) $value);
    }
    
    /**
     * Get all the events on the schedule that are due
     * 
     * @param DateTimeInterface|null $now The current time
     * @return array<Event> The due events
     */
    public function dueEvents(?DateTimeInterface $now = null): array
    {
        $now = $now ?: new DateTimeImmutable('now', new DateTimeZone($this->timezone));
        
        return array_filter($this->events, function ($event) use ($now) {
            return $event->isDue($now);
        });
    }
    
    /**
     * Get all the events on the schedule
     * 
     * @return array<Event> All events
     */
    public function events(): array
    {
        return $this->events;
    }
    
    /**
     * Set the timezone for the schedule
     * 
     * @param string $timezone
     * @return $this
     * @throws InvalidArgumentException If the timezone is invalid
     */
    public function timezone(string $timezone): self
    {
        if (!in_array($timezone, timezone_identifiers_list())) {
            throw new InvalidArgumentException("The timezone [{$timezone}] is not valid.");
        }
        
        $this->timezone = $timezone;
        
        foreach ($this->events as $event) {
            $event->timezone($timezone);
        }
        
        return $this;
    }
    
    /**
     * Get the timezone for the schedule
     * 
     * @return string
     */
    public function getTimezone(): string
    {
        return $this->timezone;
    }
} 