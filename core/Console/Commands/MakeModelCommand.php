<?php

namespace Portfolion\Console\Commands;

use Portfolion\Console\Command;

class MakeModelCommand extends Command
{
    /**
     * Command name
     */
    protected string $name = 'make:model';
    
    /**
     * Command description
     */
    protected string $description = 'Create a new model class';
    
    /**
     * Execute the command
     *
     * @param array $args Command arguments
     * @return int 0 if everything went fine, or an exit code
     */
    public function execute(array $args): int
    {
        if (empty($args)) {
            $this->error('Model name required.');
            $this->line('Usage: php portfolion make:model ModelName');
            return 1;
        }
        
        $name = $args[0];
        
        // Build the model path
        $path = 'app/Models/' . $name . '.php';
        
        // Check if model already exists
        if (file_exists($path)) {
            $this->error("Model {$name} already exists.");
            return 1;
        }
        
        // Create the model
        $content = $this->getModelTemplate($name);
        
        // Ensure directory exists
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        
        // Write the model file
        if (file_put_contents($path, $content)) {
            $this->info("Model {$name} created successfully.");
            return 0;
        } else {
            $this->error("Failed to create model {$name}.");
            return 1;
        }
    }
    
    /**
     * Get the model template
     *
     * @param string $name Model name
     * @return string
     */
    protected function getModelTemplate(string $name): string
    {
        $namespace = 'App\\Models';
        $table = $this->getTableName($name);
        
        return <<<PHP
<?php

namespace {$namespace};

use Portfolion\Database\Model;

class {$name} extends Model
{
    /**
     * The table associated with the model.
     *
     * @var ?string
     */
    protected ?string \$table = '{$table}';
    
    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected string \$primaryKey = 'id';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected array \$fillable = [
        // Define your fillable attributes here
    ];
    
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected array \$hidden = [];
    
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected array \$casts = [];
}
PHP;
    }
    
    /**
     * Get the table name from model name
     *
     * @param string $name
     * @return string
     */
    protected function getTableName(string $name): string
    {
        $name = preg_replace('/(?<!^)[A-Z]/', '_$0', $name);
        return strtolower($name) . 's';
    }
} 