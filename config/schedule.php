<?php

/**
 * Task Scheduling Configuration
 *
 * This file defines scheduled tasks for the application.
 * Use the Schedule class to define tasks that should run at specific intervals.
 */

use Portfolion\Schedule\Schedule;

return function (Schedule $schedule) {
    // Run a command every day at midnight
    $schedule->command('cache:clear')
             ->daily();
    
    // Run a command every hour
    $schedule->command('queue:work --stop-when-empty')
             ->hourly();
    
    // Run a command every Monday at 8:00 AM
    $schedule->command('app:send-weekly-report')
             ->weekly()
             ->mondays()
             ->at('8:00');
    
    // Run a custom function every 30 minutes
    $schedule->call(function () {
        // Clean up temporary files
        $tempDir = storage_path('app/temp');
        $files = glob($tempDir . '/*');
        $now = time();
        
        foreach ($files as $file) {
            if (is_file($file)) {
                if ($now - filemtime($file) >= 60 * 60) { // 1 hour old
                    unlink($file);
                }
            }
        }
    })->everyThirtyMinutes();
    
    // Run a job class every day at 1:00 AM
    $schedule->job('App\Jobs\DatabaseBackup')
             ->dailyAt('1:00')
             ->environments(['production']);
    
    // Run a shell command every day at 3:00 AM
    $schedule->exec('php -r "file_put_contents(\'storage/logs/last_exec.log\', date(\'Y-m-d H:i:s\'));"')
             ->dailyAt('3:00');
}; 