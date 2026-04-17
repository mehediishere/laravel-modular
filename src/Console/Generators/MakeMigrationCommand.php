<?php

namespace Mehediishere\LaravelModular\Console\Generators;

class MakeMigrationCommand extends BaseGeneratorCommand
{
    protected $signature = 'module:make-migration
                            {name   : Migration name (e.g. create_products_table)}
                            {module : Module name (e.g. POS)}
                            {--create= : Table name for create migration}
                            {--table=  : Table name for update migration}
                            {--force}';

    protected $description = 'Generate a migration inside a module';
    protected string $stubName = 'migration';

    public function handle(): int
    {
        $name   = $this->argument('name');
        $module = $this->argument('module');

        if (!$this->moduleExists($module)) {
            $this->components->error("Module [{$module}] does not exist.");
            return self::FAILURE;
        }

        $timestamp = date('Y_m_d_His');
        $fileName  = "{$timestamp}_{$name}";
        $base      = config('modules.path', base_path('Modules'));
        $dir       = "{$base}/{$module}/database/migrations";
        $path      = "{$dir}/{$fileName}.php";

        \Illuminate\Support\Facades\File::ensureDirectoryExists($dir);

        $tableName = $this->option('create') ?? $this->option('table') ?? $this->guessTableName($name);
        $isCreate  = $this->option('create') || str_starts_with($name, 'create_');
        $this->stubName = $isCreate ? 'migration.create' : 'migration.update';

        $content = str_replace(
            ['{{ table }}', '{{ class }}'],
            [$tableName, $this->migrationClassName($name)],
            \Illuminate\Support\Facades\File::get($this->resolveStub())
        );

        \Illuminate\Support\Facades\File::put($path, $content);

        $this->components->info("Created: Modules/{$module}/database/migrations/{$fileName}.php");

        return self::SUCCESS;
    }

    private function migrationClassName(string $name): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
    }

    private function guessTableName(string $name): string
    {
        // create_products_table → products
        if (preg_match('/^create_(.+)_table$/', $name, $m)) return $m[1];
        if (preg_match('/^add_.+_to_(.+)$/', $name, $m))    return $m[1];
        if (preg_match('/^update_(.+)$/', $name, $m))        return $m[1];
        return $name;
    }
}
