<?php

namespace Mehediishere\LaravelModular\Console\Generators;

class MakeCastCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-cast {name} {module} {--force}';
    protected $description  = 'Generate an Eloquent cast class inside a module';
    protected string $targetSubDir = 'Casts';
    protected string $stubName     = 'cast';
    protected string $classSuffix  = 'Cast';
}
