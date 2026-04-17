<?php

namespace Mehediishere\LaravelModular\Console\Generators;

class MakeJobCommand extends BaseGeneratorCommand
{
    protected $signature = 'module:make-job
                            {name}
                            {module}
                            {--sync  : Create a synchronous (non-queueable) job}
                            {--force}';

    protected $description  = 'Generate a job class inside a module';
    protected string $targetSubDir = 'Jobs';
    protected string $stubName     = 'job';

    protected function buildContent(string $className, string $module): string
    {
        $this->stubName = $this->option('sync') ? 'job.sync' : 'job';
        return parent::buildContent($className, $module);
    }
}
