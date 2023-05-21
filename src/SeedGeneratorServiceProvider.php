<?php

namespace TYGHaykal\LaravelSeedGenerator;

use Illuminate\Support\ServiceProvider;
use TYGHaykal\LaravelSeedGenerator\Commands\SeedGeneratorAsk;
use TYGHaykal\LaravelSeedGenerator\Commands\SeedGeneratorInline;
use TYGHaykal\LaravelSeedGenerator\Commands\SeedGeneratorCommand;

class SeedGeneratorServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([SeedGeneratorCommand::class]);
        }
    }
}
