<?php

namespace Mehediishere\LaravelModular\Console\Utility;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;

class EventMapCommand extends Command
{
    protected $signature   = 'module:events {module? : Optional module name to filter}
                              {--static : Use static file scanning instead of runtime resolution}';
    protected $description = 'Display a map of all inter-module events and their listeners';

    public function handle(): int
    {
        $filterModule = $this->argument('module');
        $useStatic    = $this->option('static');

        $this->newLine();

        if ($useStatic) {
            $map = $this->buildStaticMap($filterModule);
        } else {
            $map = $this->buildRuntimeMap($filterModule);

            if (empty($map)) {
                $this->components->warn('No listeners resolved at runtime. Falling back to static scan.');
                $map = $this->buildStaticMap($filterModule);
            }
        }

        if (empty($map)) {
            $this->components->warn('No events or listeners found.');
            return self::SUCCESS;
        }

        foreach ($map as $event => $listeners) {
            $this->line("  <fg=cyan;options=bold>{$event}</>");

            foreach ($listeners as $listener) {
                $this->line("    <fg=gray>→</> {$listener}");
            }

            $this->newLine();
        }

        $total = array_sum(array_map('count', $map));
        $this->line("  <fg=gray>" . count($map) . " event(s) · {$total} listener(s) total</>");
        $this->newLine();

        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // Runtime approach — resolves from Laravel's booted event dispatcher
    // -------------------------------------------------------------------------

    private function buildRuntimeMap(?string $filterModule): array
    {
        $dispatcher = Event::getFacadeRoot();

        // getRawListeners() returns the full listener array from the dispatcher
        $raw = method_exists($dispatcher, 'getRawListeners')
            ? $dispatcher->getRawListeners()
            : [];

        if (empty($raw)) {
            return [];
        }

        $map     = [];
        $modules = $this->getTargetModules($filterModule);

        foreach ($raw as $event => $listenerGroups) {
            $relevantListeners = [];

            foreach ($listenerGroups as $listener) {
                $listenerName = $this->resolveListenerName($listener);

                if (!$listenerName) {
                    continue;
                }

                // Only show listeners or events that belong to our modules
                $belongsToModule = $this->belongsToAnyModule($listenerName, $modules)
                    || $this->belongsToAnyModule($event, $modules);

                if ($belongsToModule) {
                    $relevantListeners[] = $listenerName;
                }
            }

            if (!empty($relevantListeners)) {
                $map[$event] = $relevantListeners;
            }
        }

        ksort($map);
        return $map;
    }

    private function resolveListenerName(mixed $listener): ?string
    {
        if (is_string($listener)) {
            return $listener;
        }

        if (is_array($listener) && count($listener) === 2) {
            [$class, $method] = $listener;
            $class = is_object($class) ? get_class($class) : $class;
            return "{$class}@{$method}";
        }

        if ($listener instanceof \Closure) {
            $ref = new \ReflectionFunction($listener);
            return 'Closure in ' . $ref->getFileName() . ':' . $ref->getStartLine();
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Static fallback — scans ServiceProvider files for Event::listen calls
    // -------------------------------------------------------------------------

    private function buildStaticMap(?string $filterModule): array
    {
        $map     = [];
        $modules = $this->getTargetModules($filterModule);
        $base    = config('modules.path', base_path('Modules'));

        foreach ($modules as $module) {
            $spPath = "{$base}/{$module}/app/Providers/{$module}ServiceProvider.php";

            if (!file_exists($spPath)) {
                continue;
            }

            $content = file_get_contents($spPath);

            // Match Event::listen(EventClass::class, ListenerClass::class)
            preg_match_all(
                '/Event::listen\s*\(\s*\\\\?([\w\\\\]+)::class\s*,\s*\\\\?([\w\\\\]+)::class\s*\)/m',
                $content,
                $matches,
                PREG_SET_ORDER
            );

            foreach ($matches as $match) {
                $event    = $match[1];
                $listener = $match[2];

                if (!isset($map[$event])) {
                    $map[$event] = [];
                }

                $map[$event][] = "{$listener} <fg=gray>[static]</>";
            }
        }

        ksort($map);
        return $map;
    }

    // -------------------------------------------------------------------------

    private function getTargetModules(?string $filterModule): array
    {
        if ($filterModule) {
            return [$filterModule];
        }

        return config('modules.enabled', []);
    }

    private function belongsToAnyModule(string $name, array $modules): bool
    {
        foreach ($modules as $module) {
            if (str_contains($name, "Modules\\{$module}\\")) {
                return true;
            }
        }

        return false;
    }
}
