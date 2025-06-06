<?php

namespace Portfolion\View;

use Portfolion\Config\Config;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;

class BladeViewEngine implements ViewEngineInterface
{
    /**
     * @var Factory Blade view factory
     */
    private Factory $factory;
    
    /**
     * @var array View paths
     */
    private array $paths = [];
    
    /**
     * @var array Shared data
     */
    private array $shared = [];
    
    /**
     * BladeViewEngine constructor.
     */
    public function __construct()
    {
        // Create container
        $container = new Container();
        
        // Create filesystem
        $filesystem = new Filesystem();
        
        // Create view paths
        $this->paths = [Config::get('view.paths.blade', 'resources/views')];
        
        // Create view finder
        $viewFinder = new FileViewFinder($filesystem, $this->paths);
        
        // Create blade compiler
        $bladeCompiler = new BladeCompiler(
            $filesystem,
            Config::get('view.blade.cache', 'storage/cache/blade')
        );
        
        // Register custom blade directives
        $this->registerBladeDirectives($bladeCompiler);
        
        // Create engine resolver
        $engineResolver = new EngineResolver();
        $engineResolver->register('blade', function () use ($bladeCompiler) {
            return new CompilerEngine($bladeCompiler);
        });
        
        // Create event dispatcher
        $eventDispatcher = new Dispatcher($container);
        
        // Create view factory
        $this->factory = new Factory(
            $engineResolver,
            $viewFinder,
            $eventDispatcher
        );
        
        // Share data with the factory
        foreach ($this->shared as $key => $value) {
            $this->factory->share($key, $value);
        }
    }
    
    /**
     * Render a view
     *
     * @param string $view View name
     * @param array $data View data
     * @return string Rendered view
     * @throws \Exception If view not found
     */
    public function render(string $view, array $data = []): string
    {
        // Replace dots with directory separators if not using dot notation
        if (!str_contains($view, '.')) {
            $view = str_replace('/', '.', $view);
        }
        
        // Remove .blade.php extension if present
        if (str_ends_with($view, '.blade.php')) {
            $view = substr($view, 0, -10);
        }
        
        // Merge shared data with view data
        $data = array_merge($this->shared, $data);
        
        try {
            return $this->factory->make($view, $data)->render();
        } catch (\Exception $e) {
            throw new \Exception("Error rendering view '{$view}': " . $e->getMessage());
        }
    }
    
    /**
     * Check if a view exists
     *
     * @param string $view View name
     * @return bool
     */
    public function exists(string $view): bool
    {
        // Replace dots with directory separators if not using dot notation
        if (!str_contains($view, '.')) {
            $view = str_replace('/', '.', $view);
        }
        
        // Remove .blade.php extension if present
        if (str_ends_with($view, '.blade.php')) {
            $view = substr($view, 0, -10);
        }
        
        return $this->factory->exists($view);
    }
    
    /**
     * Share data with all views
     *
     * @param string $key Data key
     * @param mixed $value Data value
     * @return void
     */
    public function share(string $key, $value): void
    {
        $this->shared[$key] = $value;
        
        if (isset($this->factory)) {
            $this->factory->share($key, $value);
        }
    }
    
    /**
     * Add a view path
     *
     * @param string $path Path to views
     * @return void
     */
    public function addPath(string $path): void
    {
        if (!in_array($path, $this->paths)) {
            $this->paths[] = $path;
            
            if (isset($this->factory)) {
                $this->factory->getFinder()->addLocation($path);
            }
        }
    }
    
    /**
     * Get the Blade factory
     *
     * @return Factory
     */
    public function getFactory(): Factory
    {
        return $this->factory;
    }
    
    /**
     * Register custom Blade directives
     *
     * @param BladeCompiler $compiler
     * @return void
     */
    private function registerBladeDirectives(BladeCompiler $compiler): void
    {
        // @route directive
        $compiler->directive('route', function ($expression) {
            return "<?php echo route({$expression}); ?>";
        });
        
        // @asset directive
        $compiler->directive('asset', function ($expression) {
            return "<?php echo asset({$expression}); ?>";
        });
        
        // @config directive
        $compiler->directive('config', function ($expression) {
            return "<?php echo config({$expression}); ?>";
        });
        
        // @auth directive
        $compiler->directive('auth', function () {
            return "<?php if(auth()->check()): ?>";
        });
        
        // @endauth directive
        $compiler->directive('endauth', function () {
            return "<?php endif; ?>";
        });
        
        // @guest directive
        $compiler->directive('guest', function () {
            return "<?php if(!auth()->check()): ?>";
        });
        
        // @endguest directive
        $compiler->directive('endguest', function () {
            return "<?php endif; ?>";
        });
        
        // @model directive
        $compiler->directive('model', function ($expression) {
            return "<?php \$model = (new App\\Models\\{$expression}()); ?>";
        });
        
        // @find directive
        $compiler->directive('find', function ($expression) {
            list($model, $id) = explode(',', $expression);
            $model = trim($model);
            $id = trim($id);
            return "<?php \$item = App\\Models\\{$model}::find({$id}); ?>";
        });
        
        // @all directive
        $compiler->directive('all', function ($expression) {
            return "<?php \$items = App\\Models\\{$expression}::all(); ?>";
        });
        
        // @where directive
        $compiler->directive('where', function ($expression) {
            list($model, $column, $value) = explode(',', $expression);
            $model = trim($model);
            $column = trim($column);
            $value = trim($value);
            return "<?php \$items = App\\Models\\{$model}::where({$column}, {$value})->get(); ?>";
        });
    }
} 