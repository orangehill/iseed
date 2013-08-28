<?php namespace Orangehill\Iseed;

use Illuminate\Support\ServiceProvider;

class IseedServiceProvider extends ServiceProvider {

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
        $this->package('orangehill/iseed');
    }

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app['iseed'] = $this->app->share(function($app)
		{
			return new Iseed;
		});
		$this->app->booting(function()
		{
			$loader = \Illuminate\Foundation\AliasLoader::getInstance();
			$loader->alias('Iseed', 'Orangehill\Iseed\Facades\Iseed');
		});

		$this->app['command.iseed'] = $this->app->share(function($app)
		{
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

}
