<?php

namespace TYGHaykal\LaravelSeedGenerator;

use TYGHaykal\LaravelSeedGenerator\Commands\SeedGeneratorCommand;
use Illuminate\Support\ServiceProvider;

class SeedGeneratorServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([SeedGeneratorCommand::class]);
        }
    }
}
