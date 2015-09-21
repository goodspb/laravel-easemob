<?php namespace Goodspb\LaravelEasemob;

use Illuminate\Support\ServiceProvider;

class LaravelEasemobServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/easemob.php' => config_path('easemob.php'),
        ]);
        $this->mergeConfigFrom(__DIR__.'/config/easemob.php', 'easemob');
    }

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
        $this->app->bind('easemob', function ($app) {
            return new easemob($app);
        });
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return [];
	}

}
