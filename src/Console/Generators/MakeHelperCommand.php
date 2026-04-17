<?php

namespace Mehediishere\LaravelModular\Console\Generators;

class MakeHelperCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-helper {name} {module} {--force}';
    protected $description  = 'Generate a helper class inside a module';
    protected string $targetSubDir = 'Helpers';
    protected string $stubName     = 'helper';
    protected string $classSuffix  = 'Helper';
}
