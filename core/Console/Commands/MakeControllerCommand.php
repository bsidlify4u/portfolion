<?php

namespace Portfolion\Console\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
     * The console input instance.
     */
    protected InputInterface $input;

    /**
     * Configure the command options.
     */
    protected function configure(): void
    {
        parent::configure();

        $this
            ->addOption('resource', 'r', InputOption::VALUE_NONE, 'Create a resource controller')
            ->addOption('api', null, InputOption::VALUE_NONE, 'Create an API controller')
            ->addOption('model', 'm', InputOption::VALUE_OPTIONAL, 'Create a controller for a model');
    }
    
    /**
     * Execute the command
     *
     * @param array $args Command arguments
     * @return int 0 if everything went fine, or an exit code
     */
    public function execute(array $args): int
    {
        if (empty($args)) {
            $this->error('Controller name required.');
            $this->line('Usage: php portfolion make:controller ControllerName');
            return 1;
        }
        
        $name = $args[0];
        
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
        
        // Create the controller
        $content = $this->getControllerTemplate($name);
        
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
     * @return string
     */
    protected function getControllerTemplate(string $name): string
    {
        $namespace = 'App\\Controllers';
        
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
        // Find and delete resource
        
        return redirect('/{$this->getRoutePrefix($name)}')->with('success', 'Resource deleted successfully');
    }
}
PHP;
    }
    
    /**
     * Get the view prefix from controller name
     *
     * @param string $name
     * @return string
     */
    protected function getViewPrefix(string $name): string
    {
        $name = str_replace('Controller', '', $name);
        return strtolower($name);
    }
    
    /**
     * Get the route prefix from controller name
     *
     * @param string $name
     * @return string
     */
    protected function getRoutePrefix(string $name): string
    {
        $name = str_replace('Controller', '', $name);
        return strtolower($name);
    }
}