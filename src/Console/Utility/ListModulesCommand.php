<?php

namespace Mehediishere\LaravelModular\Console\Utility;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class ListModulesCommand extends Command
{
    protected $signature   = 'module:list';
    protected $description = 'List all modules with their status, route count, and migration state';

    public function handle(): int
    {
        $modulesPath = config('modules.path', base_path('Modules'));
        $enabled     = config('modules.enabled', []);

        if (!is_dir($modulesPath)) {
            $this->components->error("Modules directory not found: {$modulesPath}");
            return self::FAILURE;
        }

        $allModules = array_map(
            'basename',
            glob($modulesPath . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR)
        );

        if (empty($allModules)) {
            $this->components->warn('No modules found.');
            return self::SUCCESS;
        }

        $rows = [];

        foreach ($allModules as $module) {
            $isEnabled = in_array($module, $enabled);
            $rows[]    = [
                $module,
                $isEnabled
                    ? '<fg=green>Enabled</>'
                    : '<fg=red>Disabled</>',
                $this->countRoutes($module, $modulesPath),
                $this->migrationStatus($module, $modulesPath),
                $this->hasSidebar($module, $modulesPath)
                    ? '<fg=green>Yes</>'
                    : '<fg=yellow>No</>',
            ];
        }

        $this->table(
            ['Module', 'Status', 'Routes', 'Migrations', 'Sidebar'],
            $rows
        );

        $total   = count($allModules);
        $active  = count($enabled);
        $this->newLine();
        $this->line("  <fg=gray>Total: {$total} modules · {$active} enabled · " . ($total - $active) . " disabled</>");

        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------

    private function countRoutes(string $module, string $base): string
    {
        $web = "{$base}/{$module}/routes/web.php";
        $api = "{$base}/{$module}/routes/api.php";

        $count = 0;

        // Count Route:: calls as a rough proxy — avoids booting routes
        foreach ([$web, $api] as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                $count  += preg_match_all('/Route::(get|post|put|patch|delete|any|resource|apiResource)\s*\(/i', $content);
            }
        }

        return $count > 0 ? (string) $count : '<fg=gray>0</>';
    }

    private function migrationStatus(string $module, string $base): string
    {
        $migrationsPath = "{$base}/{$module}/database/migrations";

        if (!is_dir($migrationsPath)) {
            return '<fg=gray>None</>';
        }

        $files = glob("{$migrationsPath}/*.php");

        if (empty($files)) {
            return '<fg=gray>None</>';
        }

        // Check if any migrations are pending using the migrator
        try {
            $ran     = app('migrator')->getRepository()->getRan();
            $pending = 0;

            foreach ($files as $file) {
                $migrationName = pathinfo($file, PATHINFO_FILENAME);
                if (!in_array($migrationName, $ran)) {
                    $pending++;
                }
            }

            $total = count($files);

            return $pending > 0
                ? "<fg=yellow>{$pending} pending / {$total}</>"
                : "<fg=green>Up to date ({$total})</>";
        } catch (\Exception) {
            return '<fg=gray>' . count($files) . ' file(s)</>';
        }
    }

    private function hasSidebar(string $module, string $base): bool
    {
        return file_exists("{$base}/{$module}/config/sidebar.php");
    }
}
