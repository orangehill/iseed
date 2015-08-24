**Inverse seed generator (iSeed)** is a Laravel package that provides a method to generate a new seed file based on data from the existing database table.

[![Build Status](https://travis-ci.org/orangehill/iseed.png)](http://travis-ci.org/orangehill/iseed)
[![Latest Stable Version](https://poser.pugx.org/orangehill/iseed/v/stable.png)](https://packagist.org/packages/orangehill/iseed) [![Total Downloads](https://poser.pugx.org/orangehill/iseed/downloads.png)](https://packagist.org/packages/orangehill/iseed)

## Installation

1) For Laravel 5 instalation edit your project's `composer.json` file to require `orangehill/iseed`.

    "require": {
		"orangehill/iseed": "dev-master"
	}

If you wish to install it on Laravel 4 you should require 1.1 version:

    "require": {
		"orangehill/iseed": "1.1"
	}

2) Add the service provider by opening a `app/config/app.php` file, and adding a new item to the `providers` array.

    'Orangehill\Iseed\IseedServiceProvider'

3) Update Composer from the CLI:

    composer update


## Usage

To generate a seed file for your users table simply call: `\Iseed::generateSeed('users', 'connectionName', 'numOfRows');`. `connectionName` and `numOfRows` are not required arguments. 

This will create a file inside a `/database/seeds` (`/app/database/seeds` for Laravel 4), with the contents similar to following example:

	<?php

	// File: /database/seeds/UsersTableSeeder.php

	class UsersTableSeeder extends Seeder {

		/**
		 * Auto generated seed file
		 *
		 * @return void
		 */
		public function run()
		{
			\DB::table('users')->truncate();
			\DB::table('users')->insert(array (
				0 =>
				array (
					'id' => '1',
					'email' => 'admin@admin.com',
					'password' => '$2y$10$tUGCkQf/0NY3w1l9sobGsudt6UngnoVXx/lUoh9ElcSOD0ERRkK9C',
					'permissions' => NULL,
					'activated' => '1',
					'activation_code' => NULL,
					'activated_at' => NULL,
					'last_login' => NULL,
					'persist_code' => NULL,
					'reset_password_code' => NULL,
					'first_name' => NULL,
					'last_name' => NULL,
					'created_at' => '2013-06-11 07:47:40',
					'updated_at' => '2013-06-11 07:47:40',
				),
				1 =>
				array (
					'id' => '2',
					'email' => 'user@user.com',
					'password' => '$2y$10$ImNvsMzK/BOgNSYgpjs/3OjMKMHeA9BH/hjl43EiuBuLkZGPMuZ2W',
					'permissions' => NULL,
					'activated' => '1',
					'activation_code' => NULL,
					'activated_at' => NULL,
					'last_login' => '2013-06-11 07:54:57',
					'persist_code' => '$2y$10$C0la8WuyqC6AU2TpUwj0I.E3Mrva8A3tuVFWxXN5u7jswRKzsYYHK',
					'reset_password_code' => NULL,
					'first_name' => NULL,
					'last_name' => NULL,
					'created_at' => '2013-06-11 07:47:40',
					'updated_at' => '2013-06-11 07:54:57',
				),
			));
		}

	}

This command will also update `/database/seeds/DatabaseSeeder.php` (`/app/database/seeds/DatabaseSeeder.php` for Laravel 4) to include a call to this newly generated seed class. 

If you wish you can define custom iSeed template in which all the calls will be placed. You can do this by using `#iseed_start` and `#iseed_end` templates anywhere  within `/database/seeds/DatabaseSeeder.php` (`/app/database/seeds/DatabaseSeeder.php` for Laravel 4), for example: 

	<?php

	// File: /database/seeds/DatabaseSeeder.php
	class DatabaseSeeder extends Seeder {

		/**
		 * Run the database seeds.
		 *
		 * @return void
		 */
		public function run()
		{
			Eloquent::unguard();

		    if(App::environment() == "local")
		    {
		        throw new \Exception('Only run this from production');
		    }
			
			#iseed_start
			
			// here all the calls for newly generated seeds will be stored. 

			#iseed_end
		}

	}

Alternatively you can run Iseed from the command line using Artisan, e.g. `php artisan iseed users`. For generation of multiple seed files comma separated list of table names should be send as an argument for command, e.g. `php artisan iseed users,posts,groups`.

In case you try to generate seed file that already exists command will ask you a question whether you want to overwrite it or not. If you wish to overwrite it by default use `--force` Artisan Command Option, e.g. `php artisan iseed users --force`.

If you wish to clear iSeed template you can use Artisan Command Option `--clean`, e.g. `php artisan iseed users --clean`. This will clean template from `app/database/seeds/DatabaseSeeder.php` before creating new seed class.

You can specify db connection that will be used for creation of new seed files by using Artisan Command Option `--database=connection_name`, e.g. `php artisan iseed users --database=mysql2`. 

To limit number of rows that will be exported from table use Artisan Command Option `--max=number_of_rows`, e.g. `php artisan iseed users --max=10`. If you use this option while exporting multiple tables specified limit will be applied to all of them.  

To export database complete use `--all=true` and instead of the names of the tables indicates `all`. Example: 

    php artisan iseed --all=true all

To (re)seed the database go to the Terminal and run Laravel's `db:seed command` (`php artisan db:seed`).

Please note that some users encountered a problem with large DB table exports ([error when seeding from table with many records](https://github.com/orangehill/iseed/issues/4)). The issue was solved by splitting input data into smaller chunks of elements per insert statement. As you may need to change the chunk size value in some extreme cases where DB table has a large number of columns, the chunk size is configurable in iSeed's `config.php` file:

	'chunk_size' => 500 // Maximum number of rows per insert statement

