<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Nwidart\Modules\Facades\Module;

/**
 * Initialize Module Command
 *
 * This command automates the creation of a standardized module structure.
 * It follows the "Moduler Dashboard" architectural patterns, including:
 * - DTOs for data consistency.
 * - Repositories for data access logic.
 * - Interfaces for decoupling.
 * - API Resources for response formatting.
 * - Multi-language support (translation tables).
 */
class InitModule extends Command
{
    /**
     * The name and signature of the console command.
     * {name} is the module name (e.g., Blog, Product).
     */
    protected $signature = 'app:init-module {name}';

    /**
     * The console command description.
     */
    protected $description = 'Initialize a comprehensive module structure with boilerplate code following project patterns';

    /**
     * Execute the console command.
     *
     * Orchestrates the creation of various module components. It prompts the user
     * for each optional component to ensure only necessary files are generated.
     */
    public function handle(): void
    {
        // Normalize names: Singular, SnakeCase, and PascalCase
        $modelName = str()->ucfirst($this->argument('name'));
        $modelNameSmallCase = str()->snake($this->argument('name'));
        $modelNameSingular = str()->singular($this->argument('name'));

        // Check if module already exists to avoid overwriting
        if (Module::has($modelName)) {
            $this->error("{$modelName} already exists");

            return;
        }

        /**
         * Define standard commands for each component.
         * These leverage the nwidart/laravel-modules package and project-specific DTO command.
         */
        $commands = [
            'Module' => "module:make {$modelName}",
            'Main table' => "module:make-migration create_{$modelNameSmallCase}_table {$modelName}",
            // Support for "Translated Table" pattern for multi-language fields
            'Translated table' => "module:make-migration create_{$modelNameSmallCase}_translate_table {$modelName} --fields=''",
            'Model' => "module:make-model {$modelName} {$modelName}",
            'Interface' => "module:make-interface {$modelName}Interface {$modelName}",
            'Repository' => "module:make-repository {$modelName}Repository {$modelName}",
            'Create Request' => "module:make-request Create{$modelName}Request {$modelName}",
            'Update Request' => "module:make-request Update{$modelName}Request {$modelName}",
            'Resource' => "module:make-resource {$modelName}Resource {$modelName}",
            'DTO' => "make:dto {$modelName} {$modelName}",
            'Controller' => "module:make-controller --api {$modelName}Controller {$modelName}",
            'Policy' => "module:make-policy {$modelName}Policy {$modelName}",
            'TestCase' => "make:test-case {$modelName}",
        ];

        /**
         * Mapping for components that require placeholder replacement in their stubs.
         * This ensures generated files follow exact naming conventions and namespaces.
         */
        $changeVariable = [
            'Controller' => ['path' => '/app/Http/Controllers/', 'file' => "{$modelName}Controller"],
            'Repository' => ['path' => '/app/Repositories/', 'file' => "{$modelName}Repository"],
            'Model' => ['path' => '/app/Models/', 'file' => "{$modelName}"],
            'Resource' => ['path' => '/app/Resources/', 'file' => "{$modelName}Resource"],
            'Provider' => ['path' => '/app/Providers/', 'file' => "{$modelName}ServiceProvider"],
        ];

        foreach ($commands as $type => $command) {
            // The root Module must always be created first
            if ($type === 'Module') {
                Artisan::call($command);
                $this->info("{$type} created successfully");
            } else {
                // Interactive prompt for optional components
                if ($this->confirm("Do you need to create {$type} file?", true)) {
                    // Pre-fill the translated table migration with standard localization fields
                    if ($type === 'Translated table') {
                        $command .= " --fields='{$modelNameSingular}_id:foreignId:constrained('{$modelNameSmallCase}'):cascadeOnDelete,name:string,lang:string'";
                    }

                    Artisan::call($command);

                    // If the component requires placeholder replacement, process it
                    if (array_key_exists($type, $changeVariable)) {
                        $this->replacePlaceHolder($modelName, $changeVariable[$type]['path'], $changeVariable[$type]['file']);
                    }
                    $this->info("{$type} created successfully");
                }
            }
        }

        $this->info("Your {$modelName} Module created successfully");
    }

    /**
     * Replace placeholders in generated stubs.
     *
     * This method reads the newly generated file and replaces placeholders like
     * {Module}, {module}, and {singular-module} with the actual module names
     * to ensure the boilerplate code is ready for use.
     *
     * @param string $modelName  The module name in PascalCase
     * @param string $path       The relative path to the file directory within the module
     * @param string $file       The filename without extension
     */
    private function replacePlaceHolder($modelName, $path, $file): void
    {
        $modelNameSingular = str()->singular($this->argument('name'));
        $modelNameSingular = str()->snake($modelNameSingular);

        $changes = [
             "{ControllerName}" => "{$modelName}Controller",
            "{RepositoryName}" => "{$modelName}Repository",
            "{InterfaceName}" => "{$modelName}Interface",
            "{CreateRequestName}" => "Create{$modelName}Request",
            "{UpdateRequestName}" => "Update{$modelName}Request",
            "{DtoName}" => "{$modelName}DTO",
            "{ModelName}" => "{$modelName}",
            "{ResourceName}" => "{$modelName}Resource",
            '{Module}' => $modelName,
            '{module}' => str()->snake($this->argument('name')),
            '{singular-module}' => $modelNameSingular
        ];
        $variables = array_keys($changes);
        $replace = array_values($changes);
        $bathWillBeChange = base_path().'/Modules/'.$modelName.$path.$file.'.php';
        $stub = File::get($bathWillBeChange);
        $content = str_replace($variables, $replace, $stub);
        File::replace($bathWillBeChange, $content);
    }
}
