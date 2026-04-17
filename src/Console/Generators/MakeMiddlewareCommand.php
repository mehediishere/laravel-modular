<?php

namespace Mehediishere\LaravelModular\Console\Generators;

class MakeMiddlewareCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-middleware {name} {module} {--force}';
    protected $description  = 'Generate a middleware class inside a module';
    protected string $targetSubDir = 'Http/Middleware';
    protected string $stubName     = 'middleware';
    protected string $classSuffix  = 'Middleware';
}
