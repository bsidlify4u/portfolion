<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Portfolion\Http\Controller;
use Portfolion\Http\Request;
use Portfolion\Http\Response;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $tasks = Task::all();
        
        // Convert tasks to array for Twig and add timestamps
        $tasksArray = array_map(function($task) {
            $taskData = $task->toArray();
            
            // Add timestamps if they don't exist
            if (!isset($taskData['created_at'])) {
                $taskData['created_at'] = date('Y-m-d H:i:s');
            }
            
            if (!isset($taskData['updated_at'])) {
                $taskData['updated_at'] = date('Y-m-d H:i:s');
            }
            
            return $taskData;
        }, $tasks);
        
        return new Response($this->view('tasks/index', [
            'tasks' => $tasksArray
        ]));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        return new Response($this->view('tasks/create'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): Response
    {
        $data = $request->all();
        $errors = $this->validate($data, [
            'title' => 'required',
            'description' => 'required',
            'status' => 'required',
            'due_date' => 'required'
        ]);
        
        if (!empty($errors)) {
            return new Response($this->view('tasks/create', [
                'errors' => $errors,
                'data' => $data
            ]));
        }
        
        // Format the due_date as a proper DateTime object
        if (isset($data['due_date']) && !empty($data['due_date'])) {
            try {
                // Create a DateTime object from the input date string
                $dueDate = new \DateTime($data['due_date']);
                // Format it as Y-m-d to strip any time component
                $data['due_date'] = $dueDate->format('Y-m-d');
            } catch (\Exception $e) {
                // If there's an error parsing the date, use the current date
                $data['due_date'] = date('Y-m-d');
            }
        }
        
        $task = Task::create($data);
        
        return $this->redirect('/tasks');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, int $id): Response
    {
        $task = Task::find($id);
        
        if (!$task) {
            return new Response('Task not found', 404);
        }
        
        // Convert task to array and add timestamps
        $taskData = $task->toArray();
        
        // Add timestamps if they don't exist
        if (!isset($taskData['created_at'])) {
            $taskData['created_at'] = date('Y-m-d H:i:s');
        }
        
        if (!isset($taskData['updated_at'])) {
            $taskData['updated_at'] = date('Y-m-d H:i:s');
        }
        
        return new Response($this->view('tasks/show', [
            'task' => $taskData
        ]));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, int $id): Response
    {
        $task = Task::find($id);
        
        if (!$task) {
            return new Response('Task not found', 404);
        }
        
        // Convert task to array and add timestamps
        $taskData = $task->toArray();
        
        // Add timestamps if they don't exist
        if (!isset($taskData['created_at'])) {
            $taskData['created_at'] = date('Y-m-d H:i:s');
        }
        
        if (!isset($taskData['updated_at'])) {
            $taskData['updated_at'] = date('Y-m-d H:i:s');
        }
        
        return new Response($this->view('tasks/edit', [
            'task' => $taskData
        ]));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id): Response
    {
        $task = Task::find($id);
        
        if (!$task) {
            return new Response('Task not found', 404);
        }
        
        $data = $request->all();
        error_log("Raw update data: " . print_r($data, true));
        
        $errors = $this->validate($data, [
            'title' => 'required',
            'description' => 'required',
            'status' => 'required',
            'due_date' => 'required'
        ]);
        
        if (!empty($errors)) {
            // Convert task to array and add timestamps
            $taskData = $task->toArray();
            
            // Add timestamps if they don't exist
            if (!isset($taskData['created_at'])) {
                $taskData['created_at'] = date('Y-m-d H:i:s');
            }
            
            if (!isset($taskData['updated_at'])) {
                $taskData['updated_at'] = date('Y-m-d H:i:s');
            }
            
            return new Response($this->view('tasks/edit', [
                'errors' => $errors,
                'task' => $taskData
            ]));
        }
        
        // Format the due_date as a proper date string
        if (isset($data['due_date']) && !empty($data['due_date'])) {
            try {
                // Create a DateTime object from the input date string
                $dueDate = new \DateTime($data['due_date']);
                // Format it as Y-m-d to strip any time component
                $data['due_date'] = $dueDate->format('Y-m-d');
                error_log("Formatted due_date: " . $data['due_date']);
            } catch (\Exception $e) {
                // If there's an error parsing the date, use the current date
                $data['due_date'] = date('Y-m-d');
                error_log("Error parsing date, using current date: " . $data['due_date']);
            }
        } else {
            error_log("No due_date provided or empty value");
        }
        
        error_log("Task before update: " . print_r($task->toArray(), true));
        $result = $task->update($data);
        error_log("Update result: " . ($result ? 'true' : 'false'));
        error_log("Task after update: " . print_r($task->toArray(), true));
        
        return $this->redirect('/tasks');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, int $id): Response
    {
        $task = Task::find($id);
        
        if (!$task) {
            return new Response('Task not found', 404);
        }
        
        $task->delete();
        
        return $this->redirect('/tasks');
    }
} 