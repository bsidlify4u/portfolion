<?php

namespace App\Controllers;

use Portfolion\Http\Request;
use Portfolion\Http\Response;

class BlogController
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        return view('blog/index');
    }
    
    /**
     * Display the form to create a new resource.
     *
     * @param Request $request
     * @return Response
     */
    public function create(Request $request): Response
    {
        return view('blog/create');
    }
    
    /**
     * Store a newly created resource.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request): Response
    {
        // Validate request
        $validated = $request->validate([
            // Add validation rules
        ]);
        
        // Create resource
        
        return redirect('/blog')->with('success', 'Resource created successfully');
    }
    
    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function show(Request $request, int $id): Response
    {
        // Find resource
        
        return view('blog/show');
    }
    
    /**
     * Display the form to edit the specified resource.
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function edit(Request $request, int $id): Response
    {
        // Find resource
        
        return view('blog/edit');
    }
    
    /**
     * Update the specified resource.
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, int $id): Response
    {
        // Find resource
        
        // Validate request
        $validated = $request->validate([
            // Add validation rules
        ]);
        
        // Update resource
        
        return redirect('/blog/'. $id)->with('success', 'Resource updated successfully');
    }
    
    /**
     * Delete the specified resource.
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function destroy(Request $request, int $id): Response
    {
        // Find and delete resource
        
        return redirect('/blog')->with('success', 'Resource deleted successfully');
    }
}