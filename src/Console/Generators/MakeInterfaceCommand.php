<?php

namespace Mehediishere\LaravelModular\Console\Generators;

class MakeInterfaceCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-interface {name} {module} {--force}';
    protected $description  = 'Generate a contract/interface inside a module';
    protected string $targetSubDir = 'Contracts';
    protected string $stubName     = 'interface';
    protected string $classSuffix  = 'Interface';
}
