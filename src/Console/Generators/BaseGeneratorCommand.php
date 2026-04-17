<?php

namespace Mehediishere\LaravelModular\Console\Generators;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

abstract class BaseGeneratorCommand extends Command
{
    /**
     * Subdirectory inside the module's app/ folder.
     * Override in each generator.
     */
    protected string $targetSubDir = '';

    /**
     * Stub file name (without .stub extension).
     * Override in each generator.
     */
    protected string $stubName = '';

    /**
     * File suffix appended to the class name (e.g. 'Controller', 'Request').
     * Leave empty if the class name is used as-is.
     */
    protected string $classSuffix = '';

    public function handle(): int
    {
        $name   = $this->argument('name');
        $module = $this->argument('module');

        if (!$this->moduleExists($module)) {
            $this->components->error("Module [{$module}] does not exist or is not enabled.");
            return self::FAILURE;
        }

        $className = $this->resolveClassName($name);
        $path      = $this->resolvePath($module, $className);

        if (File::exists($path) && !$this->option('force')) {
            $this->components->error("File already exists: {$path}");
            return self::FAILURE;
        }

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $this->buildContent($className, $module));

        $relative = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path);
        $this->components->info("Created: {$relative}");

        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------

    protected function moduleExists(string $module): bool
    {
        $path = config('modules.path', base_path('Modules')) . DIRECTORY_SEPARATOR . $module;
        return is_dir($path);
    }

    protected function resolveClassName(string $name): string
    {
        // Strip suffix if user already typed it (e.g. PostController → PostController)
        if ($this->classSuffix && str_ends_with($name, $this->classSuffix)) {
            return $name;
        }

        return $name . $this->classSuffix;
    }

    protected function resolvePath(string $module, string $className): string
    {
        $base = config('modules.path', base_path('Modules'));
        $dir  = $this->targetSubDir
            ? "app/{$this->targetSubDir}"
            : 'app';

        return "{$base}/{$module}/{$dir}/{$className}.php";
    }

    protected function buildContent(string $className, string $module): string
    {
        $stub = $this->resolveStub();

        return str_replace(
            ['{{ module }}', '{{ module_lower }}', '{{ class }}', '{{ namespace }}'],
            [$module, strtolower($module), $className, $this->resolveNamespace($module)],
            File::get($stub)
        );
    }

    protected function resolveStub(): string
    {
        $custom  = base_path("stubs/modular/{$this->stubName}.stub");
        $default = __DIR__ . "/../../../stubs/generators/{$this->stubName}.stub";

        return file_exists($custom) ? $custom : $default;
    }

    protected function resolveNamespace(string $module): string
    {
        $subNs = $this->targetSubDir
            ? '\\' . str_replace('/', '\\', $this->targetSubDir)
            : '';

        return "Modules\\{$module}\\app{$subNs}";
    }
}
