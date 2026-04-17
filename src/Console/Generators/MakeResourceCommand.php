<?php

namespace Mehediishere\LaravelModular\Console\Generators;

class MakeResourceCommand extends BaseGeneratorCommand
{
    protected $signature = 'module:make-resource
                            {name}
                            {module}
                            {--collection : Generate a resource collection}
                            {--force}';

    protected $description  = 'Generate an API resource inside a module';
    protected string $targetSubDir = 'Http/Resources';
    protected string $stubName     = 'resource';
    protected string $classSuffix  = 'Resource';

    protected function buildContent(string $className, string $module): string
    {
        $this->stubName = $this->option('collection') ? 'resource.collection' : 'resource';
        return parent::buildContent($className, $module);
    }
}
