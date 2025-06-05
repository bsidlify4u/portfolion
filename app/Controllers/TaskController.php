<?php

namespace App\Controllers;

use Portfolion\Http\Request;
use Portfolion\Http\Response;
use App\Models\Task;

class TaskController
{
    /**
     * Display a listing of tasks.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $tasks = Task::all();
        return view('tasks/index', ['tasks' => $tasks]);
    }
    
    /**
     * Display the form to create a new task.
     *
     * @param Request $request
     * @return Response
     */
    public function create(Request $request): Response
    {
        return view('tasks/create');
    }
    
    /**
     * Store a newly created task.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request): Response
    {
        try {
            $validated = $request->validate([
                'title' => 'required|max:255',
                'description' => 'nullable',
                'status' => 'required|in:pending,in_progress,completed,cancelled',
                'priority' => 'required|integer',
                'due_date' => 'nullable|date'
            ]);
            
            $task = new Task();
            $task->title = $validated['title'];
            $task->description = $validated['description'];
            $task->status = $validated['status'];
            $task->priority = $validated['priority'];
            $task->due_date = $validated['due_date'];
            $task->user_id = null;
            $task->save();
            
            return redirect('/tasks')->with('success', 'Task created successfully');
        } catch (\InvalidArgumentException $e) {
            // Log the validation error
            error_log('Validation error: ' . $e->getMessage());
            
            // If validation fails, return to the form with the error
            return view('tasks/create', [
                'data' => $request->all(),
                'errors' => ['validation' => $e->getMessage()]
            ]);
        }
    }
    
    /**
     * Display the specified task.
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function show(Request $request, int $id): Response
    {
        $task = Task::find($id);
        
        if (!$task) {
            return redirect('/tasks')->with('error', 'Task not found');
        }
        
        return view('tasks/show', ['task' => $task]);
    }
    
    /**
     * Display the form to edit the specified task.
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function edit(Request $request, int $id): Response
    {
        $task = Task::find($id);
        
        if (!$task) {
            return redirect('/tasks')->with('error', 'Task not found');
        }
        
        return view('tasks/edit', ['task' => $task]);
    }
    
    /**
     * Update the specified task.
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, int $id): Response
    {
        $task = Task::find($id);
        
        if (!$task) {
            return redirect('/tasks')->with('error', 'Task not found');
        }
        
        try {
            $validated = $request->validate([
                'title' => 'required|max:255',
                'description' => 'nullable',
                'status' => 'required|in:pending,in_progress,completed,cancelled',
                'priority' => 'required|integer',
                'due_date' => 'nullable|date'
            ]);
            
            $task->title = $validated['title'];
            $task->description = $validated['description'];
            $task->status = $validated['status'];
            $task->priority = $validated['priority'];
            $task->due_date = $validated['due_date'];
            $task->save();
            
            return redirect('/tasks/' . $id)->with('success', 'Task updated successfully');
        } catch (\InvalidArgumentException $e) {
            // Log the validation error
            error_log('Validation error: ' . $e->getMessage());
            
            // If validation fails, return to the form with the error
            return view('tasks/edit', [
                'task' => $task,
                'errors' => ['validation' => $e->getMessage()]
            ]);
        }
    }
    
    /**
     * Delete the specified task.
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function destroy(Request $request, int $id): Response
    {
        $task = Task::find($id);
        
        if (!$task) {
            return redirect('/tasks')->with('error', 'Task not found');
        }
        
        $task->delete();
        
        return redirect('/tasks')->with('success', 'Task deleted successfully');
    }
} 