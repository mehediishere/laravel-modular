<?php

namespace Mehediishere\LaravelModular\Console\Translation;

use Illuminate\Support\Facades\File;

class LangSyncCommand extends LangCheckCommand
{
    protected $signature   = 'module:lang-sync
                              {module? : Module name. If omitted, syncs all enabled modules.}
                              {--locale= : Sync only a specific locale (e.g. bn, ar, fr)}
                              {--dry-run : Show what would be added without writing files}';
    protected $description = 'Auto-fill missing translation keys from the English baseline';

    public function handle(): int
    {
        $modules    = $this->resolveModules();
        $isDryRun   = $this->option('dry-run');
        $locale     = $this->option('locale');
        $totalAdded = 0;

        if ($isDryRun) {
            $this->components->warn('Dry run — no files will be written.');
            $this->newLine();
        }

        foreach ($modules as $module) {
            $added = $this->syncModule($module, $locale, $isDryRun);
            $totalAdded += $added;

            if ($added === 0) {
                $this->components->info("[{$module}] Nothing to sync.");
            }
        }

        $this->newLine();

        if ($isDryRun) {
            $this->components->warn("{$totalAdded} key(s) would be added. Remove --dry-run to apply.");
        } else {
            $this->components->info("{$totalAdded} key(s) synced across all modules.");
        }

        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------

    private function syncModule(string $module, ?string $filterLocale, bool $isDryRun): int
    {
        $langPath = $this->langPath($module);

        if (!is_dir($langPath)) {
            return 0;
        }

        $enKeys  = $this->loadLocaleKeys($langPath, 'en');
        $locales = $this->getLocales($langPath, $filterLocale);
        $added   = 0;

        foreach ($locales as $locale) {
            if ($locale === 'en') {
                continue;
            }

            foreach ($enKeys as $file => $enTranslations) {
                $localePath = "{$langPath}/{$locale}";
                $filePath   = "{$localePath}/{$file}.php";

                // Load existing translations for this locale file (if it exists)
                $existing = file_exists($filePath) ? (require $filePath) : [];

                // Find keys missing from this locale
                $missingKeys = array_diff_key($enTranslations, $existing);

                if (empty($missingKeys)) {
                    continue;
                }

                $count = count($missingKeys);
                $added += $count;

                $this->line(
                    "  <fg=cyan>[{$module}]</> {$locale}/{$file}.php — adding {$count} key(s)"
                );

                foreach (array_keys($missingKeys) as $key) {
                    $this->line("    <fg=green>+</> {$key} = \"{$enTranslations[$key]}\"");
                }

                if (!$isDryRun) {
                    // Merge missing keys (English value as fallback) into existing
                    $merged = array_merge($existing, $missingKeys);
                    ksort($merged);

                    File::ensureDirectoryExists($localePath);
                    File::put($filePath, $this->renderPhpArray($merged));
                }
            }
        }

        return $added;
    }

    /**
     * Render a flat translations array as a clean PHP file.
     * Handles string values only (standard Laravel translation files).
     */
    private function renderPhpArray(array $translations): string
    {
        $lines = ["<?php", "", "return ["];

        foreach ($translations as $key => $value) {
            $escapedKey   = addslashes((string) $key);
            $escapedValue = addslashes((string) $value);
            $lines[]      = "    '{$escapedKey}' => '{$escapedValue}',";
        }

        $lines[] = "];";
        $lines[] = "";

        return implode("\n", $lines);
    }
}
