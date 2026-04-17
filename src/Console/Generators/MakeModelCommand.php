<?php

namespace Mehediishere\LaravelModular\Console\Generators;

class MakeModelCommand extends BaseGeneratorCommand
{
    protected $signature = 'module:make-model
                            {name   : Model class name (e.g. Product)}
                            {module : Module name (e.g. POS)}
                            {--m    : Create a migration}
                            {--c    : Create a controller}
                            {--f    : Create a factory}
                            {--s    : Create a seeder}
                            {--r    : Create a form request}
                            {--force}';

    protected $description = 'Generate a model inside a module. Combine flags freely: -m -mc -mcr -mcfsr etc.';
    protected string $targetSubDir = 'Models';
    protected string $stubName     = 'model';

    public function handle(): int
    {
        $flags = $this->resolveFlags();

        $result = parent::handle();
        if ($result !== self::SUCCESS) {
            return $result;
        }

        $name   = $this->argument('name');
        $module = $this->argument('module');

        if ($flags['m']) {
            $snake = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
            $this->call('module:make-migration', [
                'name'     => "create_{$snake}s_table",
                'module'   => $module,
                '--create' => "{$snake}s",
            ]);
        }

        if ($flags['c']) {
            $this->call('module:make-controller', ['name' => "{$name}Controller", 'module' => $module]);
        }

        if ($flags['f']) {
            $this->call('module:make-factory', ['name' => "{$name}Factory", 'module' => $module]);
        }

        if ($flags['s']) {
            $this->call('module:make-seeder', ['name' => "{$name}Seeder", 'module' => $module]);
        }

        if ($flags['r']) {
            $this->call('module:make-request', ['name' => "{$name}Request", 'module' => $module]);
        }

        return self::SUCCESS;
    }

    /**
     * Resolve which companion files to generate.
     *
     * Supports three input styles — all produce the same result:
     *
     *   Separate:       -m -c -r
     *   Combined short: -mcr        <- reads raw argv; Symfony cannot parse this natively
     *   Long flags:     --m --c --r
     *
     * When the user types `-mcr`, Symfony binds "cr" as the value of -m and
     * ignores -c and -r. We bypass this by scanning $_SERVER['argv'] for any
     * token matching -[mcfsr]{2,} and activating each letter individually.
     */
    private function resolveFlags(): array
    {
        $active = ['m' => false, 'c' => false, 'f' => false, 's' => false, 'r' => false];

        // Pass 1: combined short flags from raw argv e.g. -mcr -mfs -mcfsr
        foreach ($_SERVER['argv'] ?? [] as $token) {
            if (preg_match('/^-([mcfsr]{2,})$/', $token, $match)) {
                foreach (str_split($match[1]) as $char) {
                    $active[$char] = true;
                }
            }
        }

        // Pass 2: individually declared flags e.g. -m -c -r (space-separated)
        foreach (array_keys($active) as $flag) {
            if ($this->option($flag)) {
                $active[$flag] = true;
            }
        }

        return $active;
    }
}
