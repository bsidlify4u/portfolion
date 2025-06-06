<?php

use Portfolion\Routing\Router;

/**
 * Web Routes
 *
 * Here is where you can register web routes for your application.
 */

// Public routes
Router::get('/', 'HomeController@index');
Router::get('/twig', 'HomeController@indexTwig');
Router::get('/blade', 'HomeController@indexBlade');
Router::get('/about', 'HomeController@about');
Router::get('/contact', 'HomeController@contact');
Router::post('/contact', 'HomeController@submitContact');

// User routes
Router::get('/register', 'Auth\RegisterController@showRegistrationForm');
Router::post('/register', 'Auth\RegisterController@register');
Router::get('/login', 'Auth\LoginController@showLoginForm');
Router::post('/login', 'Auth\LoginController@login');
Router::post('/logout', 'Auth\LoginController@logout');

// Blog routes
Router::get('/posts', 'PostController@index');
Router::get('/posts/{slug}', 'PostController@show');

// Admin routes
Router::group(['prefix' => 'admin', 'middleware' => 'auth.admin'], function() {
    Router::get('/dashboard', 'Admin\DashboardController@index');
    
    // Admin post management
    Router::get('/posts', 'Admin\PostController@index');
    Router::get('/posts/create', 'Admin\PostController@create');
    Router::post('/posts', 'Admin\PostController@store');
    Router::get('/posts/{id}/edit', 'Admin\PostController@edit');
    Router::put('/posts/{id}', 'Admin\PostController@update');
    Router::delete('/posts/{id}', 'Admin\PostController@destroy');
    
    // Admin user management
    Router::get('/users', 'Admin\UserController@index');
    Router::get('/users/create', 'Admin\UserController@create');
    Router::post('/users', 'Admin\UserController@store');
    Router::get('/users/{id}/edit', 'Admin\UserController@edit');
    Router::put('/users/{id}', 'Admin\UserController@update');
    Router::delete('/users/{id}', 'Admin\UserController@destroy');
});

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