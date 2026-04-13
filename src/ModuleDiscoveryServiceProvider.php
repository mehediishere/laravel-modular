<?php

namespace Mehediishere\LaravelModular;

use Illuminate\Support\ServiceProvider;

class ModuleDiscoveryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $enabled = config('modules.enabled', []);

        foreach ($enabled as $module) {
            $provider = "Modules\\{$module}\\app\\Providers\\{$module}ServiceProvider";

            if (class_exists($provider)) {
                $this->app->register($provider);
            }
        }
    }
}
