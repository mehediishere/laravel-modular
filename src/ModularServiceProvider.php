<?php

namespace Mehediishere\LaravelModular;

use Illuminate\Support\ServiceProvider;
use Mehediishere\LaravelModular\Services\SidebarManager;

// Utility
use Mehediishere\LaravelModular\Console\Utility\ListModulesCommand;
use Mehediishere\LaravelModular\Console\Utility\EnableModuleCommand;
use Mehediishere\LaravelModular\Console\Utility\DisableModuleCommand;
use Mehediishere\LaravelModular\Console\Utility\EventMapCommand;

// Translation
use Mehediishere\LaravelModular\Console\Translation\LangCheckCommand;
use Mehediishere\LaravelModular\Console\Translation\LangSyncCommand;

// Scaffold
use Mehediishere\LaravelModular\Console\MakeModuleCommand;

// Generators
use Mehediishere\LaravelModular\Console\Generators\MakeControllerCommand;
use Mehediishere\LaravelModular\Console\Generators\MakeModelCommand;
use Mehediishere\LaravelModular\Console\Generators\MakeMigrationCommand;
use Mehediishere\LaravelModular\Console\Generators\MakeRequestCommand;
use Mehediishere\LaravelModular\Console\Generators\MakeServiceCommand;
use Mehediishere\LaravelModular\Console\Generators\MakeEventCommand;
use Mehediishere\LaravelModular\Console\Generators\MakeListenerCommand;
use Mehediishere\LaravelModular\Console\Generators\MakeJobCommand;
use Mehediishere\LaravelModular\Console\Generators\MakeCommandCommand;
use Mehediishere\LaravelModular\Console\Generators\MakeMiddlewareCommand;
use Mehediishere\LaravelModular\Console\Generators\MakeMailCommand;
use Mehediishere\LaravelModular\Console\Generators\MakeNotificationCommand;
use Mehediishere\LaravelModular\Console\Generators\MakeObserverCommand;
use Mehediishere\LaravelModular\Console\Generators\MakePolicyCommand;
use Mehediishere\LaravelModular\Console\Generators\MakeResourceCommand;
use Mehediishere\LaravelModular\Console\Generators\MakeSeederCommand;
use Mehediishere\LaravelModular\Console\Generators\MakeFactoryCommand;
use Mehediishere\LaravelModular\Console\Generators\MakeTraitCommand;
use Mehediishere\LaravelModular\Console\Generators\MakeInterfaceCommand;
use Mehediishere\LaravelModular\Console\Generators\MakeEnumCommand;
use Mehediishere\LaravelModular\Console\Generators\MakeExceptionCommand;
use Mehediishere\LaravelModular\Console\Generators\MakeCastCommand;
use Mehediishere\LaravelModular\Console\Generators\MakeScopeCommand;
use Mehediishere\LaravelModular\Console\Generators\MakeActionCommand;
use Mehediishere\LaravelModular\Console\Generators\MakeHelperCommand;
use Mehediishere\LaravelModular\Console\Generators\MakeRepositoryCommand;
use Mehediishere\LaravelModular\Console\Generators\MakeClassCommand;

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
            $this->commands($this->allCommands());
        }
    }

    private function allCommands(): array
    {
        return [
            // Scaffold
            MakeModuleCommand::class,

            // Utility
            ListModulesCommand::class,
            EnableModuleCommand::class,
            DisableModuleCommand::class,
            EventMapCommand::class,

            // Translation
            LangCheckCommand::class,
            LangSyncCommand::class,

            // Generators
            MakeControllerCommand::class,
            MakeModelCommand::class,
            MakeMigrationCommand::class,
            MakeRequestCommand::class,
            MakeServiceCommand::class,
            MakeEventCommand::class,
            MakeListenerCommand::class,
            MakeJobCommand::class,
            MakeCommandCommand::class,
            MakeMiddlewareCommand::class,
            MakeMailCommand::class,
            MakeNotificationCommand::class,
            MakeObserverCommand::class,
            MakePolicyCommand::class,
            MakeResourceCommand::class,
            MakeSeederCommand::class,
            MakeFactoryCommand::class,
            MakeTraitCommand::class,
            MakeInterfaceCommand::class,
            MakeEnumCommand::class,
            MakeExceptionCommand::class,
            MakeCastCommand::class,
            MakeScopeCommand::class,
            MakeActionCommand::class,
            MakeHelperCommand::class,
            MakeRepositoryCommand::class,
            MakeClassCommand::class,
        ];
    }

    private function registerPublishables(): void
    {
        $this->publishes([
            __DIR__ . '/../config/modular.php' => config_path('modular.php'),
            __DIR__ . '/../config/modules.php'  => config_path('modules.php'),
        ], 'modular-config');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/modular'),
        ], 'modular-views');

        $this->publishes([
            __DIR__ . '/../stubs' => base_path('stubs/modular'),
        ], 'modular-stubs');
    }
}
