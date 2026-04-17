<?php

namespace Mehediishere\LaravelModular\Console\Generators;

class MakeRepositoryCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-repository {name} {module} {--force}';
    protected $description  = 'Generate a repository class inside a module';
    protected string $targetSubDir = 'Repositories';
    protected string $stubName     = 'repository';
    protected string $classSuffix  = 'Repository';
}
