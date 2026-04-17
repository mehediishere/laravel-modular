<?php

namespace Mehediishere\LaravelModular\Console\Generators;

class MakeTraitCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-trait {name} {module} {--force}';
    protected $description  = 'Generate a trait inside a module';
    protected string $targetSubDir = 'Traits';
    protected string $stubName     = 'trait';
}
