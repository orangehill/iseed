<?php

namespace Orangehill\Iseed;

use Illuminate\Support\ServiceProvider;

class IseedServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     */
    protected bool $defer = false;

    /**
     * Bootstrap the application events.
     */
    public function boot(): void
    {
        require base_path().'/vendor/autoload.php';
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->registerResources();

        $this->app->singleton('iseed', fn ($app) => new Iseed);

        $this->app->booting(function () {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('Iseed', \Orangehill\Iseed\Facades\Iseed::class);
        });

        $this->app->singleton('command.iseed', fn ($app) => new IseedCommand);

        $this->commands('command.iseed');
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return ['iseed'];
    }

    /**
     * Register the package resources.
     */
    protected function registerResources(): void
    {
        $userConfigFile = app()->configPath().'/iseed.php';
        $packageConfigFile = __DIR__.'/../../config/config.php';
        $config = $this->app['files']->getRequire($packageConfigFile);

        if (file_exists($userConfigFile)) {
            $userConfig = $this->app['files']->getRequire($userConfigFile);
            $config = array_replace_recursive($config, $userConfig);
        }

        $this->app['config']->set('iseed::config', $config);
    }
}
