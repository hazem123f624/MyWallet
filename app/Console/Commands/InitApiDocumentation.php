<?php

namespace App\Console\Commands;

use App\Classes\TestCases\TestCasesModule;
use Illuminate\Console\Command;
use Nwidart\Modules\Facades\Module;

class InitApiDocumentation extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:init-test {name} {type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run a module documentation case and store its real API response in the documentation database';

    private $files = [];

    private int $testCasesResult = 0;

    private array $needToAddInDocumentation = [];

    private string $CaseKeyRunNow;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $modelName = str()->ucfirst($this->argument('name'));
        $type = $this->argument('type');
        if (! Module::has($modelName)) {
            $this->error("{$modelName} not exists");

            return;
        }

        // Try to load test cases from the new class-based system
        $testCasesClass = "Modules\\{$modelName}\\Tests\\Documentation\\DocumentationCases";

        if (class_exists($testCasesClass)) {
            $testCasesData = $testCasesClass::cases();
            $this->info("Loading test cases from [{$testCasesClass}]");

        } else {
            $this->error("No DocumentationCases class found for [{$modelName}] at [{$testCasesClass}]. Please use 'php artisan app:init-module' to scaffold it.");
            return;
        }
        $testCaseClass = new TestCasesModule($modelName, $type, $this->files, $testCasesData);
        $testCaseClass = $testCaseClass->handle();
        if ($testCaseClass['status']) {
            $this->info($testCaseClass['message']);
        } else {
            $this->error($testCaseClass['message']);
        }
    }
}
