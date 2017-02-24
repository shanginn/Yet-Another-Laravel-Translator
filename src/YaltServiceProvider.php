<?php

namespace Shanginn\Yalt;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Shanginn\Yalt\Support\Yaltor;

class YaltServiceProvider extends ServiceProvider
{
    /**
     * Register all the stuff.
     *
     * @return void
     */
    public function register()
    {
        AliasLoader::getInstance([
            'Yalt' => \Shanginn\Yalt\Facades\Yalt::class,
            'Translatable' => \Shanginn\Yalt\Eloquent\Concerns\Translatable::class,
        ])->register();

        $this->app->singleton('yalt', function () {
            return new Yaltor;
        });

        $this->mergeConfigFrom(
            __DIR__ . '/config/yalt.php', 'yalt'
        );
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/yalt.php' => config_path('yalt.php'),
        ], 'config');
    }
}
