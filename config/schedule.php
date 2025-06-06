<?php

/**
 * Schedule configuration for Portfolion
 * 
 * This file defines the scheduled tasks that will be run by the scheduler.
 * You can define tasks with different frequencies and customize when they run.
 */

return [
    // Daily task example - runs once per day at midnight
    [
        'description' => 'Clear expired cache entries',
        'expression' => 'daily',
        'at_hour' => 0,
        'at_minute' => 0,
        'command' => 'php portfolion cache:clear',
    ],
    
    // Hourly task example - runs once per hour
    [
        'description' => 'Process queued jobs',
        'expression' => 'hourly',
        'command' => 'php portfolion queue:work --once',
    ],
    
    // Custom schedule example - runs at specific times
    [
        'description' => 'Generate site map',
        'days_of_week' => [1, 4], // Monday and Thursday
        'hours' => [3], // At 3 AM
        'minutes' => [0], // At minute 0
        'command' => 'php portfolion sitemap:generate',
    ],
    
    // Task with PHP callback example
    [
        'description' => 'Clean temporary files',
        'expression' => 'daily',
        'at_hour' => 2,
        'at_minute' => 0,
        'callback' => function() {
            // This function will be called when the task is due
            $tempDir = 'storage/temp';
            if (is_dir($tempDir)) {
                $files = glob($tempDir . '/*');
                foreach ($files as $file) {
                    if (is_file($file) && time() - filemtime($file) > 86400) { // 24 hours
                        unlink($file);
                    }
                }
            }
        },
    ],
]; 