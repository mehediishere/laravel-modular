<?php

namespace Mehediishere\LaravelModular\Console\Generators;

class MakeActionCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-action {name} {module} {--force}';
    protected $description  = 'Generate an action class inside a module';
    protected string $targetSubDir = 'Actions';
    protected string $stubName     = 'action';
}
