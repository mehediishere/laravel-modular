<?php

namespace Mehediishere\LaravelModular;

use Illuminate\Support\ServiceProvider;
use Mehediishere\LaravelModular\Services\SidebarManager;
use Mehediishere\LaravelModular\Console\MakeModuleCommand;

class ModularServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/modular.php', 'modular');

        $this->app->singleton(SidebarManager::class);

        $this->app->register(ModuleDiscoveryServiceProvider::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'modular');

        if ($this->app->runningInConsole()) {
            $this->registerPublishables();
            $this->commands([MakeModuleCommand::class]);
        }
    }

    private function registerPublishables(): void
    {
        // Package config
        $this->publishes([
            __DIR__ . '/../config/modular.php' => config_path('modular.php'),
        ], 'modular-config');

        // Modules list config (host fills this per project)
        $this->publishes([
            __DIR__ . '/../config/modules.php' => config_path('modules.php'),
        ], 'modular-config');

        // Admin layout view
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/modular'),
        ], 'modular-views');

        // Stubs (publishable so host devs can customise scaffolding output)
        $this->publishes([
            __DIR__ . '/../stubs' => base_path('stubs/modular'),
        ], 'modular-stubs');
    }
}
