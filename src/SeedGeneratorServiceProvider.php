<?php

namespace TYGHaykal\LaravelSeedGenerator;

use Illuminate\Support\ServiceProvider;
use TYGHaykal\LaravelSeedGenerator\Commands\SeedGeneratorAsk;
use TYGHaykal\LaravelSeedGenerator\Commands\SeedGeneratorInline;
use TYGHaykal\LaravelSeedGenerator\Commands\SeedGeneratorCommand;

class SeedGeneratorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/config.php', 'seed-generator');
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes(
                [
                    __DIR__ . '/config/config.php' => config_path('seed-generator.php'),
                ],
                'config'
            );

            $this->commands([SeedGeneratorCommand::class]);
        }
    }
}
