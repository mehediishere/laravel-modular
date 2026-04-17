<?php

namespace Mehediishere\LaravelModular\Console\Generators;

class MakeEnumCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-enum {name} {module} {--force}';
    protected $description  = 'Generate a PHP enum inside a module';
    protected string $targetSubDir = 'Enums';
    protected string $stubName     = 'enum';
}
