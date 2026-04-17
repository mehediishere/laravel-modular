<?php

namespace Mehediishere\LaravelModular\Console\Generators;

class MakeCommandCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-command {name} {module} {--force}';
    protected $description  = 'Generate an Artisan command inside a module';
    protected string $targetSubDir = 'Console/Commands';
    protected string $stubName     = 'command';
    protected string $classSuffix  = 'Command';
}
