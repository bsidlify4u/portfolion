<?php

namespace Portfolion\Console\Commands;

use Portfolion\Console\Command;
use Portfolion\Config;

class ScheduleRunCommand extends Command
{
    /**
     * Command name
     */
    protected string $name = 'schedule:run';
    
    /**
     * Command description
     */
    protected string $description = 'Run the scheduled tasks';
    
    /**
     * Execute the command
     *
     * @param array $args Command arguments
     * @return int 0 if everything went fine, or an exit code
     */
    public function execute(array $args): int
    {
        try {
            $this->line("Running scheduled tasks...");
            
            // Get the schedule configuration
            $config = Config::getInstance();
            $schedule = $config->get('schedule', []);
            
            if (empty($schedule)) {
                $this->warning("No scheduled tasks found.");
                return 0;
            }
            
            // Track successful and failed tasks
            $successful = 0;
            $failed = 0;
            
            // Run each scheduled task
            foreach ($schedule as $task) {
                if (!$this->shouldRunNow($task)) {
                    continue;
                }
                
                $this->line("Running task: " . ($task['description'] ?? 'Unnamed task'));
                
                // Execute the command
                $command = $task['command'];
                
                // If it's a PHP function
                if (isset($task['callback']) && is_callable($task['callback'])) {
                    try {
                        call_user_func($task['callback']);
                        $this->info("Task executed successfully.");
                        $successful++;
                    } catch (\Exception $e) {
                        $this->error("Task failed: " . $e->getMessage());
                        $failed++;
                    }
                } 
                // If it's a shell command
                elseif ($command) {
                    $output = [];
                    $returnCode = 0;
                    exec($command, $output, $returnCode);
                    
                    if ($returnCode === 0) {
                        $this->info("Task executed successfully.");
                        $successful++;
                    } else {
                        $this->error("Task failed with code {$returnCode}");
                        $failed++;
                    }
                }
            }
            
            $this->line("Schedule run completed: {$successful} succeeded, {$failed} failed.");
            
            return ($failed > 0) ? 1 : 0;
            
        } catch (\Exception $e) {
            $this->error("Failed to run scheduled tasks: " . $e->getMessage());
            return 1;
        }
    }
    
    /**
     * Determine if the task should run based on its schedule
     *
     * @param array $task
     * @return bool
     */
    protected function shouldRunNow(array $task): bool
    {
        // Get current time information
        $now = new \DateTime();
        $currentMinute = (int) $now->format('i');
        $currentHour = (int) $now->format('H');
        $currentDayOfWeek = (int) $now->format('N'); // 1 (Monday) to 7 (Sunday)
        $currentDayOfMonth = (int) $now->format('j');
        
        // Check expressions
        if (isset($task['minutes']) && !in_array($currentMinute, (array) $task['minutes']) && $task['minutes'] !== '*') {
            return false;
        }
        
        if (isset($task['hours']) && !in_array($currentHour, (array) $task['hours']) && $task['hours'] !== '*') {
            return false;
        }
        
        if (isset($task['days_of_week']) && !in_array($currentDayOfWeek, (array) $task['days_of_week']) && $task['days_of_week'] !== '*') {
            return false;
        }
        
        if (isset($task['days_of_month']) && !in_array($currentDayOfMonth, (array) $task['days_of_month']) && $task['days_of_month'] !== '*') {
            return false;
        }
        
        // Special expressions
        if (isset($task['expression'])) {
            switch ($task['expression']) {
                case 'daily':
                    // Check if it's the scheduled time (default: midnight)
                    $hour = $task['at_hour'] ?? 0;
                    $minute = $task['at_minute'] ?? 0;
                    
                    if ($currentHour !== $hour || $currentMinute !== $minute) {
                        return false;
                    }
                    break;
                    
                case 'hourly':
                    // Check if it's the beginning of the hour
                    if ($currentMinute !== 0) {
                        return false;
                    }
                    break;
                    
                case 'every_minute':
                    // Always run
                    break;
                    
                default:
                    // Unknown expression
                    $this->warning("Unknown schedule expression: {$task['expression']}");
                    return false;
            }
        }
        
        return true;
    }
} 