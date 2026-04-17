<?php

namespace Mehediishere\LaravelModular\Console\Generators;

class MakeSeederCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-seeder {name} {module} {--force}';
    protected $description  = 'Generate a database seeder inside a module';
    protected string $targetSubDir = 'database/seeders';
    protected string $stubName     = 'seeder';
    protected string $classSuffix  = 'Seeder';

    protected function resolvePath(string $module, string $className): string
    {
        $base = config('modules.path', base_path('Modules'));
        return "{$base}/{$module}/database/seeders/{$className}.php";
    }

    protected function resolveNamespace(string $module): string
    {
        return "Modules\\{$module}\\database\\seeders";
    }
}
