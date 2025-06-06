<?php

/**
 * Task Scheduling Configuration
 *
 * This file defines the scheduled tasks for the application.
 * It must return an array of scheduled tasks.
 */

return [
    'enabled' => env('SCHEDULE_ENABLED', true),
    'tasks' => [
        // Define scheduled tasks here
        // Example:
        // [
        //     'command' => 'cache:clear',
        //     'schedule' => 'daily',
        //     'description' => 'Clear application cache'
        // ],
    ],
]; 