<?php

namespace Mehediishere\LaravelModular\Console\Translation;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class LangCheckCommand extends Command
{
    protected $signature   = 'module:lang
                              {module? : Module name. If omitted, checks all enabled modules.}
                              {--locale= : Check only a specific locale (e.g. bn, ar, fr)}';
    protected $description = 'Show missing translation keys compared to the English baseline';

    public function handle(): int
    {
        $modules       = $this->resolveModules();
        $filterLocale  = $this->option('locale');
        $totalMissing  = 0;

        foreach ($modules as $module) {
            $missing = $this->checkModule($module, $filterLocale);
            $totalMissing += array_sum(array_map('count', $missing));

            if (empty($missing)) {
                $this->components->info("[{$module}] All translations are complete.");
                continue;
            }

            $this->newLine();
            $this->line("  <fg=cyan;options=bold>[{$module}]</>");

            foreach ($missing as $locale => $keys) {
                $this->line("  <fg=yellow>  {$locale}/</> — " . count($keys) . ' missing key(s)');

                foreach ($keys as $file => $missingKeys) {
                    $this->line("    <fg=gray>{$file}.php</>");
                    foreach ($missingKeys as $key) {
                        $this->line("      <fg=red>✗</> {$key}");
                    }
                }
            }
        }

        $this->newLine();

        if ($totalMissing === 0) {
            $this->components->info('All modules are fully translated.');
        } else {
            $this->components->warn("{$totalMissing} missing key(s) found. Run <fg=cyan>module:lang-sync</> to auto-fill.");
        }

        return $totalMissing > 0 ? self::FAILURE : self::SUCCESS;
    }

    // -------------------------------------------------------------------------

    private function checkModule(string $module, ?string $filterLocale): array
    {
        $langPath = $this->langPath($module);

        if (!is_dir($langPath)) {
            return [];
        }

        $enKeys  = $this->loadLocaleKeys($langPath, 'en');
        $locales = $this->getLocales($langPath, $filterLocale);
        $missing = [];

        foreach ($locales as $locale) {
            if ($locale === 'en') {
                continue;
            }

            $localeKeys    = $this->loadLocaleKeys($langPath, $locale);
            $missingByFile = [];

            foreach ($enKeys as $file => $keys) {
                $localeFileKeys = $localeKeys[$file] ?? [];
                $missingKeys    = array_diff(array_keys($keys), array_keys($localeFileKeys));

                if (!empty($missingKeys)) {
                    $missingByFile[$file] = $missingKeys;
                }
            }

            if (!empty($missingByFile)) {
                $missing[$locale] = $missingByFile;
            }
        }

        return $missing;
    }

    protected function loadLocaleKeys(string $langPath, string $locale): array
    {
        $localePath = "{$langPath}/{$locale}";

        if (!is_dir($localePath)) {
            return [];
        }

        $result = [];

        foreach (File::files($localePath) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $name          = $file->getFilenameWithoutExtension();
            $result[$name] = require $file->getPathname();
        }

        return $result;
    }

    protected function getLocales(string $langPath, ?string $filterLocale): array
    {
        if ($filterLocale) {
            return ['en', $filterLocale];
        }

        $dirs = glob("{$langPath}/*", GLOB_ONLYDIR);

        return array_map('basename', $dirs ?: []);
    }

    protected function langPath(string $module): string
    {
        $base = config('modules.path', base_path('Modules'));
        return "{$base}/{$module}/resources/lang";
    }

    protected function resolveModules(): array
    {
        $module = $this->argument('module');

        if ($module) {
            return [$module];
        }

        return config('modules.enabled', []);
    }
}
