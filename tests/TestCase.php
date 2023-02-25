<?php

namespace Orangehill\Iseed\Tests;

use Illuminate\Support\Facades\File;
use Orangehill\Iseed\IseedServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__ . '/support');
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

    }

    public function tearDown(): void
    {
        File::deleteDirectory(base_path(config('iseed::config.path')));
    }

    /**
     * add the package provider
     *
     * @param $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [IseedServiceProvider::class];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('iseed::config.path', '\temp');
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        File::makeDirectory(base_path(config('iseed::config.path')));
        File::put(base_path(config('iseed::config.path')).'\DatabaseSeeder.php', '<?php ');

    }
}
