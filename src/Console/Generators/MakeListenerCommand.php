<?php

namespace Mehediishere\LaravelModular\Console\Generators;

class MakeListenerCommand extends BaseGeneratorCommand
{
    protected $signature = 'module:make-listener
                            {name   : Listener class name}
                            {module : Module name}
                            {--event= : The event class this listener handles}
                            {--queued  : Make the listener queueable}
                            {--force}';

    protected $description  = 'Generate an event listener inside a module';
    protected string $targetSubDir = 'Listeners';
    protected string $stubName     = 'listener';

    protected function buildContent(string $className, string $module): string
    {
        $this->stubName = $this->option('queued') ? 'listener.queued' : 'listener';
        $content = parent::buildContent($className, $module);
        $eventClass = $this->option('event') ?? 'YourEvent';
        return str_replace('{{ event }}', $eventClass, $content);
    }
}
