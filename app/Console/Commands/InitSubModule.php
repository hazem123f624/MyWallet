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
class InitSubModule extends Command
{
    /**
     * The name and signature of the console command.
     * {name} is the module name (e.g., Blog, Product).
     */
    protected $signature = 'app:init-sub-module {name} {module}';

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
        $modelName = str()->ucfirst($this->argument('module'));
        $subModelName = str()->ucfirst($this->argument('name'));
        $subModelNameSmallCase = str()->snake($this->argument('name'));
        $subModelNameSingular = str()->singular($this->argument('name'));

        // Check if module already exists to avoid overwriting
        if (!Module::has($modelName)) {
            $this->error("{$modelName} not exists");

            return;
        }

        /**
         * Define standard commands for each component.
         * These leverage the nwidart/laravel-modules package and project-specific DTO command.
         */
        $commands = [
            'Main table' => "module:make-migration create_{$subModelNameSmallCase}_table {$modelName}",
            // Support for "Translated Table" pattern for multi-language fields
            'Translated table' => "module:make-migration create_{$subModelNameSmallCase}_translate_table {$modelName} --fields=''",
            'Model' => "module:make-model {$subModelName} {$modelName}",
            'Interface' => "module:make-interface {$subModelName}Interface {$modelName}",
            'Repository' => "module:make-repository {$subModelName}Repository {$modelName}",
            'Create Request' => "module:make-request Create{$subModelName}Request {$modelName}",
            'Update Request' => "module:make-request Update{$subModelName}Request {$modelName}",
            'Resource' => "module:make-resource {$subModelName}Resource {$modelName}",
            'DTO' => "make:dto {$subModelName} {$modelName}",
            'Controller' => "module:make-controller --api {$subModelName}Controller {$modelName}",
            'Policy' => "module:make-policy {$subModelName}Policy {$modelName}",

        ];

        /**
         * Mapping for components that require placeholder replacement in their stubs.
         * This ensures generated files follow exact naming conventions and namespaces.
         */
        $changeVariable = [
            'Controller' => ['path' => '/app/Http/Controllers/', 'file' => "{$subModelName}Controller"],
            'Repository' => ['path' => '/app/Repositories/', 'file' => "{$subModelName}Repository"],
            'Model' => ['path' => '/app/Models/', 'file' => "{$subModelName}"],
            'Resource' => ['path' => '/app/Resources/', 'file' => "{$subModelName}Resource"],
        ];

        foreach ($commands as $type => $command) {

            // Interactive prompt for optional components
            if ($this->confirm("Do you need to create {$type} file?", true)) {
                // Pre-fill the translated table migration with standard localization fields
                if ($type === 'Translated table') {
                    $command .= " --fields='{$subModelNameSingular}_id:foreignId:constrained('{$subModelNameSmallCase}'):cascadeOnDelete,name:string,lang:string'";
                }

                Artisan::call($command);

                // If the component requires placeholder replacement, process it
                if (array_key_exists($type, $changeVariable)) {

                    $this->replacePlaceHolder($modelName, $changeVariable[$type]['path'], $changeVariable[$type]['file'],$subModelName);
                }
                $this->info("{$type} created successfully");
            }
        }

        $this->info("Your {$subModelName} Module created successfully");
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
    private function replacePlaceHolder($modelName, $path, $file,$suModuleName): void
    {
        $modelNameSingular = str()->singular($this->argument('name'));
        $modelNameSingular = str()->snake($modelNameSingular);
        $changes = [
            "{ControllerName}" => "{$suModuleName}Controller",
            "{RepositoryName}" => "{$suModuleName}Repository",
            "{InterfaceName}" => "{$suModuleName}Interface",
            "{CreateRequestName}" => "Create{$suModuleName}Request",
            "{UpdateRequestName}" => "Update{$suModuleName}Request",
            "{DtoName}" => "{$suModuleName}DTO",
            "{ModelName}" => "{$suModuleName}",
            "{ResourceName}" => "{$suModuleName}Resource",
            '{Module}' => $modelName,
            '{module}' => str()->snake($this->argument('name')),
            '{singular-module}' => $modelNameSingular

        ];
        $variables = array_keys($changes);
        $replace = array_values($changes);
        $bathWillBeChange = base_path().'/Modules/'.$modelName.$path.$file.'.php';

        if (File::exists($bathWillBeChange)) {
            $stub = File::get($bathWillBeChange);
            $content = str_replace($variables, $replace, $stub);
            File::replace($bathWillBeChange, $content);
        }
    }
}
