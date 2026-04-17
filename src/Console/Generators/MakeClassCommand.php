<?php

namespace Mehediishere\LaravelModular\Console\Generators;

class MakeClassCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-class {name} {module} {--force}';
    protected $description  = 'Generate a plain class inside a module app/ folder';
    protected string $targetSubDir = '';
    protected string $stubName     = 'class';
}
