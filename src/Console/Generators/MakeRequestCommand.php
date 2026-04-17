<?php

namespace Mehediishere\LaravelModular\Console\Generators;

class MakeRequestCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-request {name} {module} {--force}';
    protected $description  = 'Generate a form request inside a module';
    protected string $targetSubDir = 'Http/Requests';
    protected string $stubName     = 'request';
    protected string $classSuffix  = 'Request';
}
