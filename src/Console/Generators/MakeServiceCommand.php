<?php

namespace Mehediishere\LaravelModular\Console\Generators;

class MakeServiceCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-service {name} {module} {--force}';
    protected $description  = 'Generate a service class inside a module';
    protected string $targetSubDir = 'Services';
    protected string $stubName     = 'service';
    protected string $classSuffix  = 'Service';
}
