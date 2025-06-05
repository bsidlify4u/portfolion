<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/functions/helpers.php';

use App\Models\Task;

// Create a new task
$task = new Task();
$task->title = 'Test Task';
$task->description = 'This is a test task';
$task->status = 'pending';
$task->priority = 1;
$task->due_date = '2025-06-10';

// Try to save the task
try {
    $success = $task->save();
    echo "Task saved: " . ($success ? 'Yes' : 'No') . PHP_EOL;
    echo "Task ID: " . $task->id . PHP_EOL;
} catch (Exception $e) {
    echo "Error saving task: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace: " . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
}

// Try to fetch all tasks
try {
    $tasks = Task::all();
    echo "Found " . count($tasks) . " tasks" . PHP_EOL;
    
    foreach ($tasks as $t) {
        echo "Task ID: " . $t->id . ", Title: " . $t->title . ", Status: " . $t->status . PHP_EOL;
    }
} catch (Exception $e) {
    echo "Error fetching tasks: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace: " . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
} 