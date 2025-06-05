<?php

use Portfolion\Routing\Router;

$router = Router::getInstance();

// Home route
$router->get('/', function() {
    return redirect('/tasks');
});

// Task routes
$router->get('/tasks', [App\Controllers\TaskController::class, 'index']);
$router->get('/tasks/create', [App\Controllers\TaskController::class, 'create']);
$router->post('/tasks', [App\Controllers\TaskController::class, 'store']);
$router->get('/tasks/{id}', [App\Controllers\TaskController::class, 'show']);
$router->get('/tasks/{id}/edit', [App\Controllers\TaskController::class, 'edit']);
$router->put('/tasks/{id}', [App\Controllers\TaskController::class, 'update']);
$router->delete('/tasks/{id}', [App\Controllers\TaskController::class, 'destroy']); 