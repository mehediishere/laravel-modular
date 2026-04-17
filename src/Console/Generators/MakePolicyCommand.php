<?php

namespace Mehediishere\LaravelModular\Console\Generators;

class MakePolicyCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-policy {name} {module} {--force}';
    protected $description  = 'Generate a policy class inside a module';
    protected string $targetSubDir = 'Policies';
    protected string $stubName     = 'policy';
    protected string $classSuffix  = 'Policy';
}
