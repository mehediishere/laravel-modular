<?php

namespace Mehediishere\LaravelModular\Console\Generators;

class MakeExceptionCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-exception {name} {module} {--force}';
    protected $description  = 'Generate a custom exception inside a module';
    protected string $targetSubDir = 'Exceptions';
    protected string $stubName     = 'exception';
    protected string $classSuffix  = 'Exception';
}
