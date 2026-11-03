<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeDtoCommand extends Command
{
    protected $signature = 'make:dto {name} {module}';

    protected $description = 'Generate a new DTO class file';

    public function handle()
    {
        $name = $this->argument('name');
        $module = $this->argument('module');
        if ($module) {
            // For laravel-modules support
            $basePath = base_path("Modules/{$module}/app/DTOs");
            $namespace = "Modules\\{$module}\\DTOs";
        } else {
            // For normal Laravel app
            $basePath = app_path('DTOs');
            $namespace = 'App\\DTOs';
        }
        // Ensure directory exists
        if (! File::exists($basePath)) {
            File::makeDirectory($basePath, 0777, true);
        }
        $className = $name.'DTO';
        $filePath = "{$basePath}/{$className}.php";

        if (File::exists($filePath)) {
            $this->error('DTO already exists!');

            return;
        }

        // Load stub
        $stub = File::get(base_path('stubs/nwidart-stubs/dto.stub'));

        // Replace stub variables
        $stub = str_replace(
            ['{{ namespace }}', '{{ class }}'],
            [$namespace, $className],
            $stub
        );

        // Create DTO file
        File::put($filePath, $stub);
        $this->info("DTO {$className} created successfully at: {$filePath}");
    }
}
