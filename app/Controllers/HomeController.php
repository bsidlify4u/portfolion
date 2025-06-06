<?php
namespace App\Controllers;

use Portfolion\Http\Controller;
use Portfolion\Http\Request;
use Portfolion\Http\Response;
use Portfolion\Cache\Cache;
use Portfolion\Events\EventDispatcher;

class HomeController extends Controller {
    private $cache;
    private $events;
    
    public function __construct() {
        $this->cache = Cache::getInstance();
        $this->events = EventDispatcher::getInstance();
    }
    
    /**
     * Display the home page using the default view engine
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request) {
        return $this->view('home', [
            'title' => 'Welcome to Portfolion',
            'description' => 'A lightweight, modern PHP framework for building web applications and APIs.',
            'features' => [
                'MVC Architecture',
                'Routing System',
                'Database ORM',
                'Migration System',
                'Command Line Interface',
                'Caching System',
                'Task Scheduling',
                'Middleware Support',
                'Template Engine'
            ]
        ]);
    }

    /**
     * Display the home page using Twig
     *
     * @param Request $request
     * @return Response
     */
    public function indexTwig(Request $request) {
        return $this->view('home', [
            'title' => 'Welcome to Portfolion with Twig',
            'description' => 'A lightweight, modern PHP framework for building web applications and APIs.',
            'features' => [
                'MVC Architecture',
                'Routing System',
                'Database ORM',
                'Migration System',
                'Command Line Interface',
                'Caching System',
                'Task Scheduling',
                'Middleware Support',
                'Twig Template Engine'
            ]
        ], 'twig');
    }

    /**
     * Display the home page using Blade
     *
     * @param Request $request
     * @return Response
     */
    public function indexBlade(Request $request) {
        return $this->view('home', [
            'title' => 'Welcome to Portfolion with Blade',
            'description' => 'A lightweight, modern PHP framework for building web applications and APIs.',
            'features' => [
                'MVC Architecture',
                'Routing System',
                'Database ORM',
                'Migration System',
                'Command Line Interface',
                'Caching System',
                'Task Scheduling',
                'Middleware Support',
                'Blade Template Engine'
            ]
        ], 'blade');
    }

    /**
     * Display the about page
     *
     * @param Request $request
     * @return Response
     */
    public function about(Request $request) {
        return $this->view('about', [
            'title' => 'About Portfolion',
            'content' => 'Portfolion is a lightweight, modern PHP framework designed to make web development simple, flexible, and enjoyable.'
        ]);
    }

    /**
     * Display the contact page
     *
     * @param Request $request
     * @return Response
     */
    public function contact(Request $request) {
        return $this->view('contact', [
            'title' => 'Contact Us'
        ]);
    }

    /**
     * Process the contact form
     *
     * @param Request $request
     * @return Response
     */
    public function submitContact(Request $request) {
        // Validate the request
        $validated = $request->validate([
            'name' => 'required|min:2',
            'email' => 'required|email',
            'message' => 'required|min:10'
        ]);

        // Process the contact form (e.g., send email)
        // ...

        // Redirect with success message
        return $this->redirect('/contact')->with('success', 'Your message has been sent!');
    }
    
    public function test(Request $request) {
        return $this->json([
            'status' => 'success',
            'message' => 'Framework is working!'
        ]);
    }
}
