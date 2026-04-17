<?php

namespace Mehediishere\LaravelModular\Console\Generators;

class MakeNotificationCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-notification {name} {module} {--force}';
    protected $description  = 'Generate a notification class inside a module';
    protected string $targetSubDir = 'Notifications';
    protected string $stubName     = 'notification';
}
