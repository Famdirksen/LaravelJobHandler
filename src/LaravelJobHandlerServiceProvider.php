<?php

namespace Famdirksen\LaravelJobHandler;

use Famdirksen\LaravelJobHandler\Console\Commands\LaravelJobHandlerClearLogsCommand;
use Illuminate\Support\ServiceProvider;

class LaravelJobHandlerServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                LaravelJobHandlerClearLogsCommand::class
            ]);
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/laravel-job-handler.php', 'laravel-job-handler'
        );
    }
}
