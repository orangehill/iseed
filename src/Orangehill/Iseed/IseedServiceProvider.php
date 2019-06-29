<?php

namespace Orangehill\Iseed;

use Illuminate\Support\ServiceProvider;

class IseedServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        require base_path().'/vendor/autoload.php';
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerResources();

        $this->app->singleton('iseed', function($app) {
            return new Iseed;
        });

        $this->app->alias('Iseed', 'Orangehill\Iseed\Facades\Iseed');

        $this->app->singleton('command.iseed', function($app) {
            return new IseedCommand;
        });

        $this->commands('command.iseed');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('iseed');
    }

    /**
     * Register the package resources.
     *
     * @return void
     */
    protected function registerResources()
    {
        if (function_exists('config_path')) {
            $userConfigFile = config_path('ide-helper.php');
        } else {
            $userConfigFile = base_path('config/ide-helper.php');
        }
        $packageConfigFile = __DIR__.'/../../config/config.php';
        $config            = $this->app['files']->getRequire($packageConfigFile);

        if (file_exists($userConfigFile)) {
            $userConfig = $this->app['files']->getRequire($userConfigFile);
            $config     = array_replace_recursive($config, $userConfig);
        }

        $this->app['config']->set('iseed::config', $config);
    }
}