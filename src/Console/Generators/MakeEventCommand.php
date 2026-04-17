<?php

namespace Mehediishere\LaravelModular\Console\Generators;

class MakeEventCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-event {name} {module} {--force}';
    protected $description  = 'Generate an event class inside a module';
    protected string $targetSubDir = 'Events';
    protected string $stubName     = 'event';
}
