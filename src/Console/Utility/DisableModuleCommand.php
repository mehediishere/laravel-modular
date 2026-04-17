<?php

namespace Mehediishere\LaravelModular\Console\Utility;

use Illuminate\Console\Command;
use Mehediishere\LaravelModular\Console\Concerns\ManagesModulesConfig;

class DisableModuleCommand extends Command
{
    use ManagesModulesConfig;

    protected $signature   = 'module:disable {name : The module name to disable}';
    protected $description = 'Disable a module by removing it from config/modules.php';

    public function handle(): int
    {
        $name    = $this->argument('name');
        $enabled = config('modules.enabled', []);

        if (!in_array($name, $enabled)) {
            $this->components->warn("Module [{$name}] is not in the enabled list.");
            return self::SUCCESS;
        }

        $enabled = array_values(array_filter($enabled, fn($m) => $m !== $name));
        $this->writeEnabledList($enabled);

        $this->components->info("Module [{$name}] has been disabled.");

        return self::SUCCESS;
    }
}
