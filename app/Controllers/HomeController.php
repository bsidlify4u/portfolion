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
    
    public function index(Request $request, Response $response) {
        // Test cache
        $visits = $this->cache->remember('visits', 3600, function() {
            return 0;
        });
        
        $this->cache->put('visits', $visits + 1);
        
        // Test event dispatch
        $this->events->dispatch(new class extends \Portfolion\Events\Event {
            public $message = 'Home page visited';
        });
        
        return $this->view('home', [
            'title' => 'Welcome to Portfolion',
            'visits' => $visits + 1
        ]);
    }
    
    public function test(Request $request, Response $response) {
        return $response->json([
            'status' => 'success',
            'message' => 'Framework is working!'
        ]);
    }
}
