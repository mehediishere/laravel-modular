<?php

namespace Mehediishere\LaravelModular\Console\Generators;

class MakeObserverCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-observer {name} {module} {--force}';
    protected $description  = 'Generate an observer class inside a module';
    protected string $targetSubDir = 'Observers';
    protected string $stubName     = 'observer';
    protected string $classSuffix  = 'Observer';
}
