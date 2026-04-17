<?php

namespace Mehediishere\LaravelModular\Console\Concerns;

trait ManagesModulesConfig
{
    /**
     * Rewrite the 'enabled' array inside config/modules.php on disk.
     * Preserves all other keys in the file by doing a targeted replacement.
     */
    protected function writeEnabledList(array $enabled): void
    {
        $configPath = config_path('modules.php');

        if (!file_exists($configPath)) {
            $this->components->error('config/modules.php not found. Run: php artisan vendor:publish --tag=modular-config');
            return;
        }

        $content = file_get_contents($configPath);

        // Build the new 'enabled' array block
        $items = array_map(
            fn($m) => "        '{$m}'",
            $enabled
        );

        $newBlock = "'enabled' => [\n" . implode(",\n", $items) . "\n    ]";

        // Replace the existing enabled block using a regex
        $content = preg_replace(
            "/'enabled'\s*=>\s*\[[^\]]*\]/s",
            $newBlock,
            $content
        );

        file_put_contents($configPath, $content);
    }

    /**
     * Check if the module folder exists on disk.
     */
    protected function moduleDirectoryExists(string $name): bool
    {
        $path = config('modules.path', base_path('Modules')) . DIRECTORY_SEPARATOR . $name;
        return is_dir($path);
    }
}
