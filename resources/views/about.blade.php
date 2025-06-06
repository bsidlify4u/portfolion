@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1>{{ $title }}</h1>
            <p>{{ $content }}</p>
            
            <h2>About the Framework</h2>
            <p>
                Portfolion is a lightweight, modern PHP framework designed to make web development 
                simple, flexible, and enjoyable. It provides a clean and elegant syntax that allows 
                developers to express their creativity without getting bogged down in complex configurations.
            </p>
            
            <h2>Key Features</h2>
            <ul>
                <li><strong>MVC Architecture</strong> - Organized structure with Models, Views, and Controllers</li>
                <li><strong>Routing System</strong> - Simple and flexible routing</li>
                <li><strong>Database ORM</strong> - Intuitive database interaction</li>
                <li><strong>Migration System</strong> - Easy database schema management</li>
                <li><strong>Command Line Interface</strong> - Powerful CLI tools for development</li>
                <li><strong>Multiple Template Engines</strong> - Support for PHP, Twig, and Blade templating</li>
            </ul>
        </div>
    </div>
</div>
@endsection 