<?php

namespace Mehediishere\LaravelModular\Console\Generators;

class MakeControllerCommand extends BaseGeneratorCommand
{
    protected $signature = 'module:make-controller
                            {name   : Controller class name (e.g. PostController)}
                            {module : Module name (e.g. POS)}
                            {--plain}
                            {--api}
                            {--invokable}
                            {--force}';

    protected $description  = 'Generate a controller inside a module';
    protected string $targetSubDir = 'Http/Controllers';
    protected string $classSuffix  = 'Controller';

    protected function resolveClassName(string $name): string
    {
        // Never double-append suffix
        return str_ends_with($name, 'Controller') ? $name : $name . 'Controller';
    }

    protected function buildContent(string $className, string $module): string
    {
        $stub = match(true) {
            $this->option('api')       => 'controller.api',
            $this->option('invokable') => 'controller.invokable',
            $this->option('plain')     => 'controller.plain',
            default                    => 'controller',
        };

        $this->stubName = $stub;
        return parent::buildContent($className, $module);
    }
}
