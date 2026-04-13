<?php

namespace Mehediishere\LaravelModular;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

abstract class BaseModuleServiceProvider extends ServiceProvider
{
    /**
     * The module name. Must be set in every extending class.
     * Must match the folder name exactly (PascalCase). e.g. 'POS', 'Ecommerce'.
     */
    protected string $moduleName = '';

    /**
     * Interface => Concrete bindings registered in the container.
     *
     * Example:
     *   protected array $bindings = [
     *       ProductRepositoryInterface::class => ProductRepository::class,
     *   ];
     */
    protected array $bindings = [];

    /**
     * Singletons registered in the container.
     * Can be keyed (string key => class) or indexed (class only).
     *
     * Example:
     *   protected array $singletons = [
     *       'currency' => CurrencyService::class,
     *       PaymentGateway::class,
     *   ];
     */
    protected array $singletons = [];

    /**
     * Artisan command classes to register.
     *
     * Example:
     *   protected array $commands = [
     *       SyncProductCatalogCommand::class,
     *   ];
     */
    protected array $commands = [];

    // -------------------------------------------------------------------------
    // Boot & Register
    // -------------------------------------------------------------------------

    public function register(): void
    {
        $this->registerBindings();
        $this->registerConfigs();
    }

    public function boot(): void
    {
        $this->registerMigrations();
        $this->registerViews();
        $this->registerTranslations();
        $this->registerRoutes();
        $this->registerCommands();
    }

    // -------------------------------------------------------------------------
    // Registration helpers
    // -------------------------------------------------------------------------

    protected function registerBindings(): void
    {
        foreach ($this->singletons as $key => $class) {
            $abstract = is_int($key) ? $class : $key;
            $this->app->singleton($abstract, $class);
        }

        foreach ($this->bindings as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }
    }

    protected function registerConfigs(): void
    {
        $path = $this->modulePath('config/config.php');

        if (file_exists($path)) {
            $this->mergeConfigFrom($path, strtolower($this->moduleName));
        }
    }

    protected function registerMigrations(): void
    {
        $path = $this->modulePath('database/migrations');

        if (is_dir($path)) {
            $this->loadMigrationsFrom($path);
        }
    }

    protected function registerViews(): void
    {
        $path = $this->modulePath('resources/views');

        if (is_dir($path)) {
            $this->loadViewsFrom($path, strtolower($this->moduleName));
        }
    }

    protected function registerTranslations(): void
    {
        $path = $this->modulePath('resources/lang');

        if (is_dir($path)) {
            $this->loadTranslationsFrom($path, strtolower($this->moduleName));
        }
    }

    protected function registerRoutes(): void
    {
        $web = $this->modulePath('routes/web.php');
        $api = $this->modulePath('routes/api.php');

        if (file_exists($web)) {
            Route::middleware('web')->group($web);
        }

        if (file_exists($api)) {
            Route::middleware('api')->prefix('api')->group($api);
        }
    }

    protected function registerCommands(): void
    {
        if ($this->commands && $this->app->runningInConsole()) {
            $this->commands($this->commands);
        }
    }

    // -------------------------------------------------------------------------
    // Sidebar
    // -------------------------------------------------------------------------

    /**
     * Return this module's sidebar group definition.
     * Called by SidebarManager during sidebar build.
     *
     * The config/sidebar.php file in the module should return an array matching:
     *
     *   [
     *     'group'  => 'Module Name',
     *     'icon'   => 'icon-key',
     *     'order'  => 10,
     *     'items'  => [
     *         ['label' => '...', 'route' => '...', 'icon' => '...', 'order' => 1, 'permission' => '...'],
     *     ],
     *   ]
     */
    public function sidebarLinks(): array
    {
        $path = $this->modulePath('config/sidebar.php');

        return file_exists($path) ? require $path : [];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve an absolute path within this module's folder.
     */
    protected function modulePath(string $relative = ''): string
    {
        $base = config('modules.path', base_path('Modules'));

        return $base
            . DIRECTORY_SEPARATOR
            . $this->moduleName
            . ($relative ? DIRECTORY_SEPARATOR . $relative : '');
    }

    /**
     * Expose the module name for external use (e.g. SidebarManager).
     */
    public function getModuleName(): string
    {
        return $this->moduleName;
    }
}
