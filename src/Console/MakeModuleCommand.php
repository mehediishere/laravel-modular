<?php

namespace Mehediishere\LaravelModular\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeModuleCommand extends Command
{
    protected $signature = 'module:make
                            {name : PascalCase module name (e.g. POS, Ecommerce, Account)}
                            {--force : Overwrite if module already exists}';

    protected $description = 'Scaffold a new module with full folder structure';

    /**
     * Directories created inside every module.
     */
    private array $dirs = [
        'app/Http/Controllers',
        'app/Http/Requests',
        'app/Models',
        'app/Services',
        'app/Contracts',
        'app/Providers',
        'app/Console/Commands',
        'app/Events',
        'app/Listeners',
        'config',
        'database/migrations',
        'database/seeders',
        'database/factories',
        'resources/views',
        'resources/lang/en',
        'routes',
        'tests/Feature',
        'tests/Unit',
    ];

    public function handle(): int
    {
        $name  = $this->argument('name');
        $base  = config('modules.path', base_path('Modules')) . DIRECTORY_SEPARATOR . $name;
        $force = $this->option('force');

        if (is_dir($base) && !$force) {
            $this->components->error("Module [{$name}] already exists. Use --force to overwrite.");
            return self::FAILURE;
        }

        $this->components->info("Scaffolding module [{$name}]...");

        $this->createDirectories($base);
        $this->writeStub('service-provider', "{$base}/app/Providers/{$name}ServiceProvider.php", $name);
        $this->writeStub('sidebar-config',   "{$base}/config/sidebar.php", $name);
        $this->writeStub('module-config',    "{$base}/config/config.php", $name);
        $this->writeStub('routes-web',       "{$base}/routes/web.php", $name);
        $this->writeStub('routes-api',       "{$base}/routes/api.php", $name);
        $this->writeStub('test-case',        "{$base}/tests/TestCase.php", $name);

        $this->newLine();
        $this->components->success("Module [{$name}] created successfully.");
        $this->printNextSteps($name);

        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------

    private function createDirectories(string $base): void
    {
        foreach ($this->dirs as $dir) {
            $fullPath = "{$base}/{$dir}";
            File::makeDirectory($fullPath, 0755, true, true);
            // Placeholder so git tracks empty directories
            File::put("{$fullPath}/.gitkeep", '');
        }
    }

    private function writeStub(string $stubName, string $destination, string $name): void
    {
        // Host can publish stubs and customise them — those take priority
        $customStub  = base_path("stubs/modular/{$stubName}.stub");
        $defaultStub = __DIR__ . "/../../stubs/{$stubName}.stub";

        $stubPath = file_exists($customStub) ? $customStub : $defaultStub;

        $contents = str_replace(
            ['{{ module }}', '{{ module_lower }}', '{{ module_upper }}'],
            [$name, strtolower($name), strtoupper($name)],
            File::get($stubPath)
        );

        File::put($destination, $contents);
    }

    private function printNextSteps(string $name): void
    {
        $lower = strtolower($name);

        $this->newLine();
        $this->line('  <fg=yellow;options=bold>Next steps:</>');
        $this->newLine();
        $this->line("  <fg=white>1.</> Add <fg=cyan>'{$name}'</> to the <fg=cyan>enabled</> array in <fg=cyan>config/modules.php</>");
        $this->line("  <fg=white>2.</> Run <fg=cyan>composer dump-autoload</>");
        $this->line("  <fg=white>3.</> Run <fg=cyan>php artisan migrate</> to pick up new migrations");
        $this->line("  <fg=white>4.</> Add a test suite entry to <fg=cyan>phpunit.xml</>:");
        $this->line("     <fg=gray><testsuite name=\"{$name}\"><directory>Modules/{$name}/tests</directory></testsuite</>");
        $this->line("  <fg=white>5.</> Edit <fg=cyan>Modules/{$name}/config/sidebar.php</> to set the group_id and sidebar links");
        $this->newLine();
    }
}
