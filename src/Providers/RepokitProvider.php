<?php

namespace Sazl\LaravelRepokit\Providers;

use Illuminate\Support\ServiceProvider;
use Sazl\LaravelRepokit\Commands\MakeRepositoryCommand;
use Sazl\LaravelRepokit\Commands\MakeServiceCommand;

class RepokitProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/repository.php', 'repository');
        $this->mergeConfigFrom(__DIR__ . '/../../config/service.php', 'service');

        $this->registerBindings();
    }

    protected function registerBindings(): void
    {
        $bindings = config('service.bindings', []);

        if (!is_array($bindings)) {
            return;
        }

        foreach ($bindings as $interface => $implementation) {
            if (!is_string($interface) || !is_string($implementation)) {
                continue;
            }

            if ($interface === '' || $implementation === '') {
                continue;
            }

            if (!class_exists($interface) && !interface_exists($interface)) {
                continue;
            }

            if (!class_exists($implementation)) {
                continue;
            }

            $this->app->bind($interface, $implementation);
        }
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {

            $this->commands([
                MakeRepositoryCommand::class,
                MakeServiceCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/../../config/repository.php' => config_path('repository.php'),
            ], 'repository-config');

            $this->publishes([
                __DIR__ . '/../../config/service.php' => config_path('service.php'),
            ], 'service-config');

            // Publish stubs
            $this->publishes([
                __DIR__ . '/stubs' => resource_path('stubs/repository'),
            ], 'repository-stubs');


        }
    }
}