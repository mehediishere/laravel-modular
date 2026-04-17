<?php

/**
 * All module generator commands.
 * Each class is ~10 lines — shared logic lives in BaseGeneratorCommand.
 *
 * Usage pattern:  php artisan module:make-{type} {ClassName} {ModuleName}
 * Example:        php artisan module:make-controller PostController POS
 */

namespace Mehediishere\LaravelModular\Console\Generators;

// ---------------------------------------------------------------------------
// Controller
// ---------------------------------------------------------------------------

class MakeControllerCommand extends BaseGeneratorCommand
{
    protected $signature = 'module:make-controller
                            {name   : Controller class name (e.g. PostController)}
                            {module : Module name (e.g. POS)}
                            {--plain}
                            {--api}
                            {--invokable}
                            {--force}';

    protected $description  = 'Generate a controller inside a module';
    protected string $targetSubDir = 'Http/Controllers';
    protected string $classSuffix  = 'Controller';

    protected function resolveClassName(string $name): string
    {
        // Never double-append suffix
        return str_ends_with($name, 'Controller') ? $name : $name . 'Controller';
    }

    protected function buildContent(string $className, string $module): string
    {
        $stub = match(true) {
            $this->option('api')       => 'controller.api',
            $this->option('invokable') => 'controller.invokable',
            $this->option('plain')     => 'controller.plain',
            default                    => 'controller',
        };

        $this->stubName = $stub;
        return parent::buildContent($className, $module);
    }
}

// ---------------------------------------------------------------------------
// Model  (with -m -c -f -s -r flags and combined e.g. --mcfsr)
// ---------------------------------------------------------------------------

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
// ---------------------------------------------------------------------------
// Migration
// ---------------------------------------------------------------------------

class MakeMigrationCommand extends BaseGeneratorCommand
{
    protected $signature = 'module:make-migration
                            {name   : Migration name (e.g. create_products_table)}
                            {module : Module name (e.g. POS)}
                            {--create= : Table name for create migration}
                            {--table=  : Table name for update migration}
                            {--force}';

    protected $description = 'Generate a migration inside a module';
    protected string $stubName = 'migration';

    public function handle(): int
    {
        $name   = $this->argument('name');
        $module = $this->argument('module');

        if (!$this->moduleExists($module)) {
            $this->components->error("Module [{$module}] does not exist.");
            return self::FAILURE;
        }

        $timestamp = date('Y_m_d_His');
        $fileName  = "{$timestamp}_{$name}";
        $base      = config('modules.path', base_path('Modules'));
        $dir       = "{$base}/{$module}/database/migrations";
        $path      = "{$dir}/{$fileName}.php";

        \Illuminate\Support\Facades\File::ensureDirectoryExists($dir);

        $tableName = $this->option('create') ?? $this->option('table') ?? $this->guessTableName($name);
        $isCreate  = $this->option('create') || str_starts_with($name, 'create_');
        $this->stubName = $isCreate ? 'migration.create' : 'migration.update';

        $content = str_replace(
            ['{{ table }}', '{{ class }}'],
            [$tableName, $this->migrationClassName($name)],
            \Illuminate\Support\Facades\File::get($this->resolveStub())
        );

        \Illuminate\Support\Facades\File::put($path, $content);

        $this->components->info("Created: Modules/{$module}/database/migrations/{$fileName}.php");

        return self::SUCCESS;
    }

    private function migrationClassName(string $name): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
    }

    private function guessTableName(string $name): string
    {
        // create_products_table → products
        if (preg_match('/^create_(.+)_table$/', $name, $m)) return $m[1];
        if (preg_match('/^add_.+_to_(.+)$/', $name, $m))    return $m[1];
        if (preg_match('/^update_(.+)$/', $name, $m))        return $m[1];
        return $name;
    }
}

// ---------------------------------------------------------------------------
// Request
// ---------------------------------------------------------------------------

class MakeRequestCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-request {name} {module} {--force}';
    protected $description  = 'Generate a form request inside a module';
    protected string $targetSubDir = 'Http/Requests';
    protected string $stubName     = 'request';
    protected string $classSuffix  = 'Request';
}

// ---------------------------------------------------------------------------
// Service
// ---------------------------------------------------------------------------

class MakeServiceCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-service {name} {module} {--force}';
    protected $description  = 'Generate a service class inside a module';
    protected string $targetSubDir = 'Services';
    protected string $stubName     = 'service';
    protected string $classSuffix  = 'Service';
}

// ---------------------------------------------------------------------------
// Event
// ---------------------------------------------------------------------------

class MakeEventCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-event {name} {module} {--force}';
    protected $description  = 'Generate an event class inside a module';
    protected string $targetSubDir = 'Events';
    protected string $stubName     = 'event';
}

// ---------------------------------------------------------------------------
// Listener
// ---------------------------------------------------------------------------

class MakeListenerCommand extends BaseGeneratorCommand
{
    protected $signature = 'module:make-listener
                            {name   : Listener class name}
                            {module : Module name}
                            {--event= : The event class this listener handles}
                            {--queued  : Make the listener queueable}
                            {--force}';

    protected $description  = 'Generate an event listener inside a module';
    protected string $targetSubDir = 'Listeners';
    protected string $stubName     = 'listener';

    protected function buildContent(string $className, string $module): string
    {
        $this->stubName = $this->option('queued') ? 'listener.queued' : 'listener';
        $content = parent::buildContent($className, $module);
        $eventClass = $this->option('event') ?? 'YourEvent';
        return str_replace('{{ event }}', $eventClass, $content);
    }
}

// ---------------------------------------------------------------------------
// Job
// ---------------------------------------------------------------------------

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

// ---------------------------------------------------------------------------
// Command
// ---------------------------------------------------------------------------

class MakeCommandCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-command {name} {module} {--force}';
    protected $description  = 'Generate an Artisan command inside a module';
    protected string $targetSubDir = 'Console/Commands';
    protected string $stubName     = 'command';
    protected string $classSuffix  = 'Command';
}

// ---------------------------------------------------------------------------
// Middleware
// ---------------------------------------------------------------------------

class MakeMiddlewareCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-middleware {name} {module} {--force}';
    protected $description  = 'Generate a middleware class inside a module';
    protected string $targetSubDir = 'Http/Middleware';
    protected string $stubName     = 'middleware';
    protected string $classSuffix  = 'Middleware';
}

// ---------------------------------------------------------------------------
// Mail
// ---------------------------------------------------------------------------

class MakeMailCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-mail {name} {module} {--force}';
    protected $description  = 'Generate a mailable class inside a module';
    protected string $targetSubDir = 'Mail';
    protected string $stubName     = 'mail';
}

// ---------------------------------------------------------------------------
// Notification
// ---------------------------------------------------------------------------

class MakeNotificationCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-notification {name} {module} {--force}';
    protected $description  = 'Generate a notification class inside a module';
    protected string $targetSubDir = 'Notifications';
    protected string $stubName     = 'notification';
}

// ---------------------------------------------------------------------------
// Observer
// ---------------------------------------------------------------------------

class MakeObserverCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-observer {name} {module} {--force}';
    protected $description  = 'Generate an observer class inside a module';
    protected string $targetSubDir = 'Observers';
    protected string $stubName     = 'observer';
    protected string $classSuffix  = 'Observer';
}

// ---------------------------------------------------------------------------
// Policy
// ---------------------------------------------------------------------------

class MakePolicyCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-policy {name} {module} {--force}';
    protected $description  = 'Generate a policy class inside a module';
    protected string $targetSubDir = 'Policies';
    protected string $stubName     = 'policy';
    protected string $classSuffix  = 'Policy';
}

// ---------------------------------------------------------------------------
// Resource
// ---------------------------------------------------------------------------

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

// ---------------------------------------------------------------------------
// Seeder
// ---------------------------------------------------------------------------

class MakeSeederCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-seeder {name} {module} {--force}';
    protected $description  = 'Generate a database seeder inside a module';
    protected string $targetSubDir = 'database/seeders';
    protected string $stubName     = 'seeder';
    protected string $classSuffix  = 'Seeder';

    protected function resolvePath(string $module, string $className): string
    {
        $base = config('modules.path', base_path('Modules'));
        return "{$base}/{$module}/database/seeders/{$className}.php";
    }

    protected function resolveNamespace(string $module): string
    {
        return "Modules\\{$module}\\database\\seeders";
    }
}

// ---------------------------------------------------------------------------
// Factory
// ---------------------------------------------------------------------------

class MakeFactoryCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-factory {name} {module} {--force}';
    protected $description  = 'Generate a model factory inside a module';
    protected string $targetSubDir = 'database/factories';
    protected string $stubName     = 'factory';
    protected string $classSuffix  = 'Factory';

    protected function resolvePath(string $module, string $className): string
    {
        $base = config('modules.path', base_path('Modules'));
        return "{$base}/{$module}/database/factories/{$className}.php";
    }

    protected function resolveNamespace(string $module): string
    {
        return "Modules\\{$module}\\database\\factories";
    }
}

// ---------------------------------------------------------------------------
// Trait
// ---------------------------------------------------------------------------

class MakeTraitCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-trait {name} {module} {--force}';
    protected $description  = 'Generate a trait inside a module';
    protected string $targetSubDir = 'Traits';
    protected string $stubName     = 'trait';
}

// ---------------------------------------------------------------------------
// Interface (Contract)
// ---------------------------------------------------------------------------

class MakeInterfaceCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-interface {name} {module} {--force}';
    protected $description  = 'Generate a contract/interface inside a module';
    protected string $targetSubDir = 'Contracts';
    protected string $stubName     = 'interface';
    protected string $classSuffix  = 'Interface';
}

// ---------------------------------------------------------------------------
// Enum
// ---------------------------------------------------------------------------

class MakeEnumCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-enum {name} {module} {--force}';
    protected $description  = 'Generate a PHP enum inside a module';
    protected string $targetSubDir = 'Enums';
    protected string $stubName     = 'enum';
}

// ---------------------------------------------------------------------------
// Exception
// ---------------------------------------------------------------------------

class MakeExceptionCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-exception {name} {module} {--force}';
    protected $description  = 'Generate a custom exception inside a module';
    protected string $targetSubDir = 'Exceptions';
    protected string $stubName     = 'exception';
    protected string $classSuffix  = 'Exception';
}

// ---------------------------------------------------------------------------
// Cast
// ---------------------------------------------------------------------------

class MakeCastCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-cast {name} {module} {--force}';
    protected $description  = 'Generate an Eloquent cast class inside a module';
    protected string $targetSubDir = 'Casts';
    protected string $stubName     = 'cast';
    protected string $classSuffix  = 'Cast';
}

// ---------------------------------------------------------------------------
// Scope
// ---------------------------------------------------------------------------

class MakeScopeCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-scope {name} {module} {--force}';
    protected $description  = 'Generate an Eloquent query scope inside a module';
    protected string $targetSubDir = 'Scopes';
    protected string $stubName     = 'scope';
    protected string $classSuffix  = 'Scope';
}

// ---------------------------------------------------------------------------
// Action
// ---------------------------------------------------------------------------

class MakeActionCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-action {name} {module} {--force}';
    protected $description  = 'Generate an action class inside a module';
    protected string $targetSubDir = 'Actions';
    protected string $stubName     = 'action';
}

// ---------------------------------------------------------------------------
// Helper
// ---------------------------------------------------------------------------

class MakeHelperCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-helper {name} {module} {--force}';
    protected $description  = 'Generate a helper class inside a module';
    protected string $targetSubDir = 'Helpers';
    protected string $stubName     = 'helper';
    protected string $classSuffix  = 'Helper';
}

// ---------------------------------------------------------------------------
// Repository
// ---------------------------------------------------------------------------

class MakeRepositoryCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-repository {name} {module} {--force}';
    protected $description  = 'Generate a repository class inside a module';
    protected string $targetSubDir = 'Repositories';
    protected string $stubName     = 'repository';
    protected string $classSuffix  = 'Repository';
}

// ---------------------------------------------------------------------------
// Bare class
// ---------------------------------------------------------------------------

class MakeClassCommand extends BaseGeneratorCommand
{
    protected $signature    = 'module:make-class {name} {module} {--force}';
    protected $description  = 'Generate a plain class inside a module app/ folder';
    protected string $targetSubDir = '';
    protected string $stubName     = 'class';
}
