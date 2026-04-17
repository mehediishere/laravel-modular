<?php

namespace Mehediishere\LaravelModular\Console\Utility;

use Illuminate\Console\Command;
use Mehediishere\LaravelModular\Console\Concerns\ManagesModulesConfig;

class EnableModuleCommand extends Command
{
    use ManagesModulesConfig;

    protected $signature   = 'module:enable {name : The module name to enable}';
    protected $description = 'Enable a module by adding it to config/modules.php';

    public function handle(): int
    {
        $name    = $this->argument('name');
        $enabled = config('modules.enabled', []);

        if (!$this->moduleDirectoryExists($name)) {
            $this->components->error("Module [{$name}] folder not found in " . config('modules.path', base_path('Modules')));
            return self::FAILURE;
        }

        if (in_array($name, $enabled)) {
            $this->components->warn("Module [{$name}] is already enabled.");
            return self::SUCCESS;
        }

        $enabled[] = $name;
        $this->writeEnabledList($enabled);

        $this->components->info("Module [{$name}] has been enabled.");
        $this->line("  <fg=gray>Run <fg=cyan>composer dump-autoload</> if this is a new module.</>");

        return self::SUCCESS;
    }
}
