**Inverse seed generator (iSeed)** is a Laravel 4 package that provides a method to generate a new seed file based on data from the existing database table.

[![Build Status](https://travis-ci.org/orangehill/iseed.png)](http://travis-ci.org/orangehill/iseed)

## Installation

1) Edit your project's `composer.json` file to require `orangehill/iseed`.

    "require": {
		"laravel/framework": "4.0.*",
		"orangehill/iseed": "dev-master"
	}

2) Update Composer from the CLI:

    composer update

3) Once this operation completes, add the service provider by opening a `app/config/app.php` file, and adding a new item to the `providers` array.

    'Orangehill\Iseed\IseedServiceProvider'

## Usage

To generate a seed file for your users table simply call: `\Iseed::generateSeed('users');`

This will create a file inside a `/app/database/seeds`, with the contents similar to following example:

	<?php

	// File: /app/database/seeds/UsersTableSeeder.php

	class UsersTableSeeder extends Seeder {

		/**
		 * Auto generated seed file
		 *
		 * @return void
		 */
		public function run()
		{
			\DB::table('users')->delete();
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

This command will also update `app/database/seeds/DatabaseSeeder.php` to include a call to this newly generated seed class.

Alternatively you can run Iseed from the command line using Artisan, e.g. `php artisan iseed users`.

To (re)seed the database go to the Terminal and run Laravel's `db:seed command` (`php artisan db:seed`).
