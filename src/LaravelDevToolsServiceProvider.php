<?php

namespace ArchiElite\LaravelDevTools;

use ArchiElite\LaravelDevTools\Commands\FacadeDocblockGenerateCommand;
use Illuminate\Support\ServiceProvider;

class LaravelDevToolsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            FacadeDocblockGenerateCommand::class,
        ]);
    }
}
