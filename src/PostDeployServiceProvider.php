<?php

namespace Dblencowe\PostDeploy;

use Dblencowe\PostDeploy\Console\Commands\PostDeployMakeCommand;
use Illuminate\Support\ServiceProvider;
use Dblencowe\PostDeploy\Console\Commands\PostDeployRunCommand;

class PostDeployServiceProvider extends ServiceProvider
{
    /**
     * Register the various parts of the package with the outside system
     */
    public function register()
    {
        $this->commands([
            PostDeployRunCommand::class,
            PostDeployMakeCommand::class,
        ]);
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/Migrations/' => database_path('migrations')
        ], 'migrations');
    }
}
