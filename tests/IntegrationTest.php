<?php

namespace Orangehill\Iseed\Tests;

use Illuminate\Support\Facades\DB;

class IntegrationTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();
        DB::table('users')->truncate();

        DB::table('users')->insert([
                ['name' => 'John Doe', 'nickname' => 'jndoe', 'country' => 'United States'],
                ['name' => 'Sally Sue', 'nickname' => 'sally_sue', 'country' => 'United States'],
            ]
        );

    }

    /**
     * This is a basic test to ensure the command and seeder command are type compatible...
     *
     * @return void
     */
    public function test_integrated()
    {
        $basePath = base_path(config('iseed::config.path'));
        $this->assertFileDoesNotExist($basePath . '/UsersTableSeeder.php');
        $this->artisan('iseed users --orderby=name --direction=asc --dumpauto=false --force');
        $this->assertFileExists($basePath . '/UsersTableSeeder.php');
        $this->assertFileExists($basePath . '/DatabaseSeeder.php');
    }
}
