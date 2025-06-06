<?php

namespace Portfolion\Console\Commands;

use Portfolion\Console\Command;

class MakeControllerCommand extends Command
{
    /**
     * Command name
     */
    protected string $name = 'make:controller';
    
    /**
     * Command description
     */
    protected string $description = 'Create a new controller class';

    /**
     * The type of class being generated.
     */
    protected string $type = 'Controller';
    
    /**
     * Execute the command
     *
     * @param array $args Command arguments
     * @return int 0 if everything went fine, or an exit code
     */
    public function execute(array $args): int
    {
        // Extract non-option arguments (positional arguments)
        $controllerName = null;
        $options = [];
        
        foreach ($args as $arg) {
            if (strpos($arg, '--') === 0) {
                // This is an option, store it
                $options[] = $arg;
            } elseif ($controllerName === null) {
                // This is the controller name
                $controllerName = $arg;
            }
        }
        
        if ($controllerName === null) {
            $this->error('Controller name required.');
            $this->line('Usage: php portfolion make:controller ControllerName');
            return 1;
        }
        
        $name = $controllerName;
        
        // Add Controller suffix if not present
        if (!str_ends_with($name, 'Controller')) {
            $name .= 'Controller';
        }
        
        // Build the controller path
        $path = 'app/Controllers/' . $name . '.php';
        
        // Check if controller already exists
        if (file_exists($path)) {
            $this->error("Controller {$name} already exists.");
            return 1;
        }
        
        // Determine type of controller to create
        $isResource = in_array('--resource', $options) || in_array('-r', $options);
        $isApi = in_array('--api', $options);
        
        // Create the controller
        $content = $this->getControllerTemplate($name, $isResource, $isApi);
        
        // Ensure directory exists
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        
        // Write the controller file
        if (file_put_contents($path, $content)) {
            $this->info("Controller {$name} created successfully.");
            return 0;
        } else {
            $this->error("Failed to create controller {$name}.");
            return 1;
        }
    }
    
    /**
     * Get the controller template
     *
     * @param string $name Controller name
     * @param bool $isResource Whether to create a resource controller
     * @param bool $isApi Whether to create an API controller
     * @return string
     */
    protected function getControllerTemplate(string $name, bool $isResource = false, bool $isApi = false): string
    {
        $namespace = 'App\\Controllers';
        
        if ($isApi) {
            return $this->getApiControllerTemplate($name, $namespace);
        } elseif ($isResource) {
            return $this->getResourceControllerTemplate($name, $namespace);
        } else {
            return $this->getBasicControllerTemplate($name, $namespace);
        }
    }
    
    /**
     * Get the basic controller template
     *
     * @param string $name Controller name
     * @param string $namespace Namespace
     * @return string
     */
    protected function getBasicControllerTemplate(string $name, string $namespace): string
    {
        return <<<PHP
<?php

namespace {$namespace};

use Portfolion\Http\Request;
use Portfolion\Http\Response;

class {$name}
{
    /**
     * Handle the incoming request.
     *
     * @param Request \$request
     * @return Response
     */
    public function index(Request \$request): Response
    {
        return view('{$this->getViewPrefix($name)}/index');
    }
}
PHP;
    }
    
    /**
     * Get the resource controller template
     *
     * @param string $name Controller name
     * @param string $namespace Namespace
     * @return string
     */
    protected function getResourceControllerTemplate(string $name, string $namespace): string
    {
        return <<<PHP
<?php

namespace {$namespace};

use Portfolion\Http\Request;
use Portfolion\Http\Response;

class {$name}
{
    /**
     * Display a listing of the resource.
     *
     * @param Request \$request
     * @return Response
     */
    public function index(Request \$request): Response
    {
        return view('{$this->getViewPrefix($name)}/index');
    }
    
    /**
     * Display the form to create a new resource.
     *
     * @param Request \$request
     * @return Response
     */
    public function create(Request \$request): Response
    {
        return view('{$this->getViewPrefix($name)}/create');
    }
    
    /**
     * Store a newly created resource.
     *
     * @param Request \$request
     * @return Response
     */
    public function store(Request \$request): Response
    {
        // Validate request
        \$validated = \$request->validate([
            // Add validation rules
        ]);
        
        // Create resource
        
        return redirect('/{$this->getRoutePrefix($name)}')->with('success', 'Resource created successfully');
    }
    
    /**
     * Display the specified resource.
     *
     * @param Request \$request
     * @param int \$id
     * @return Response
     */
    public function show(Request \$request, int \$id): Response
    {
        // Find resource
        
        return view('{$this->getViewPrefix($name)}/show');
    }
    
    /**
     * Display the form to edit the specified resource.
     *
     * @param Request \$request
     * @param int \$id
     * @return Response
     */
    public function edit(Request \$request, int \$id): Response
    {
        // Find resource
        
        return view('{$this->getViewPrefix($name)}/edit');
    }
    
    /**
     * Update the specified resource.
     *
     * @param Request \$request
     * @param int \$id
     * @return Response
     */
    public function update(Request \$request, int \$id): Response
    {
        // Find resource
        
        // Validate request
        \$validated = \$request->validate([
            // Add validation rules
        ]);
        
        // Update resource
        
        return redirect('/{$this->getRoutePrefix($name)}/'. \$id)->with('success', 'Resource updated successfully');
    }
    
    /**
     * Delete the specified resource.
     *
     * @param Request \$request
     * @param int \$id
     * @return Response
     */
    public function destroy(Request \$request, int \$id): Response
    {
        // Find resource
        
        // Delete resource
        
        return redirect('/{$this->getRoutePrefix($name)}')->with('success', 'Resource deleted successfully');
    }
}
PHP;
    }
    
    /**
     * Get the API controller template
     *
     * @param string $name Controller name
     * @param string $namespace Namespace
     * @return string
     */
    protected function getApiControllerTemplate(string $name, string $namespace): string
    {
        return <<<PHP
<?php

namespace {$namespace};

use Portfolion\Http\Request;
use Portfolion\Http\Response;

class {$name}
{
    /**
     * Display a listing of the resource.
     *
     * @param Request \$request
     * @return Response
     */
    public function index(Request \$request): Response
    {
        return response()->json([
            'data' => [],
        ]);
    }
    
    /**
     * Store a newly created resource.
     *
     * @param Request \$request
     * @return Response
     */
    public function store(Request \$request): Response
    {
        // Validate request
        \$validated = \$request->validate([
            // Add validation rules
        ]);
        
        // Create resource
        
        return response()->json([
            'message' => 'Resource created successfully',
            'data' => [],
        ], 201);
    }
    
    /**
     * Display the specified resource.
     *
     * @param Request \$request
     * @param int \$id
     * @return Response
     */
    public function show(Request \$request, int \$id): Response
    {
        // Find resource
        
        return response()->json([
            'data' => [],
        ]);
    }
    
    /**
     * Update the specified resource.
     *
     * @param Request \$request
     * @param int \$id
     * @return Response
     */
    public function update(Request \$request, int \$id): Response
    {
        // Find resource
        
        // Validate request
        \$validated = \$request->validate([
            // Add validation rules
        ]);
        
        // Update resource
        
        return response()->json([
            'message' => 'Resource updated successfully',
            'data' => [],
        ]);
    }
    
    /**
     * Delete the specified resource.
     *
     * @param Request \$request
     * @param int \$id
     * @return Response
     */
    public function destroy(Request \$request, int \$id): Response
    {
        // Find resource
        
        // Delete resource
        
        return response()->json([
            'message' => 'Resource deleted successfully',
        ]);
    }
}
PHP;
    }
    
    /**
     * Get the view prefix for the controller
     *
     * @param string $name Controller name
     * @return string
     */
    protected function getViewPrefix(string $name): string
    {
        $name = str_replace('Controller', '', $name);
        return strtolower($name);
    }
    
    /**
     * Get the route prefix for the controller
     *
     * @param string $name Controller name
     * @return string
     */
    protected function getRoutePrefix(string $name): string
    {
        $name = str_replace('Controller', '', $name);
        return strtolower($name);
    }
}