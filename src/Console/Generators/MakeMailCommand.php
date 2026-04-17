<?php

namespace Mehediishere\LaravelModular\Console\Generators;

class MakeMailCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-mail {name} {module} {--force}';
    protected $description  = 'Generate a mailable class inside a module';
    protected string $targetSubDir = 'Mail';
    protected string $stubName     = 'mail';
}
