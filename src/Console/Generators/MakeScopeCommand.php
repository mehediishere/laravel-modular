<?php

namespace Mehediishere\LaravelModular\Console\Generators;

class MakeScopeCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-scope {name} {module} {--force}';
    protected $description  = 'Generate an Eloquent query scope inside a module';
    protected string $targetSubDir = 'Scopes';
    protected string $stubName     = 'scope';
    protected string $classSuffix  = 'Scope';
}
