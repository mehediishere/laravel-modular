<?php

namespace Mehediishere\LaravelModular\Console\Generators;

class MakeFactoryCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-factory {name} {module} {--force}';
    protected $description  = 'Generate a model factory inside a module';
    protected string $targetSubDir = 'database/factories';
    protected string $stubName     = 'factory';
    protected string $classSuffix  = 'Factory';

    protected function resolvePath(string $module, string $className): string
    {
        $base = config('modules.path', base_path('Modules'));
        return "{$base}/{$module}/database/factories/{$className}.php";
    }

    protected function resolveNamespace(string $module): string
    {
        return "Modules\\{$module}\\database\\factories";
    }
}
