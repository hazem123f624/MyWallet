<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeTestCaseCommand  extends  Command
{
    protected $signature = 'make:test-case {module}';

    protected $description = 'Create the documentation cases file for a module';

    public function handle()
    {
        $modelName = str()->ucfirst($this->argument('module'));
        $testPath = base_path("Modules/{$modelName}/tests/Documentation");
        if (!File::isDirectory($testPath)) {
            File::makeDirectory($testPath, 0755, true);
        }
        $filePath = "{$testPath}/DocumentationCases.php";
        if (File::exists($filePath)) {
            return;
        }
        $stub = $this->getStub($modelName);

        $variables = ['{Module}', '{module}'];
        $replace = [$modelName, str()->snake($modelName)];
        $content = str_replace($variables, $replace, $stub);
        File::put($filePath, $content);
        $this->info("Tests/Documentation/DocumentationCases.php created successfully");
    }

    public function getStub($moduleName)
    {
        return File::get(base_path('stubs/nwidart-stubs/documentation.stub'));
    }
}
