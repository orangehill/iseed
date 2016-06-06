**Inverse seed generator (iSeed)** is a Laravel package that provides a method to generate a new seed file based on data from the existing database table.

[![Build Status](https://travis-ci.org/orangehill/iseed.png)](http://travis-ci.org/orangehill/iseed)
[![Latest Stable Version](https://poser.pugx.org/orangehill/iseed/v/stable.png)](https://packagist.org/packages/orangehill/iseed) [![Total Downloads](https://poser.pugx.org/orangehill/iseed/downloads.png)](https://packagist.org/packages/orangehill/iseed)
[![Analytics](https://ga-beacon.appspot.com/UA-1936460-35/iseed?useReferrer&flat)](https://github.com/igrigorik/ga-beacon)

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

## Artisan command options

### [table_name]
Mandatory parameter which defines which table/s will be used for seed creation.
Use CSV notation for multiple tables. Seed file will be generated for each table.

Examples:
```
php artisan iseed my_table
```
```
php artisan iseed my_table,another_table
```

### force
Optional parameter which is used to automatically overwrite any existing seeds for desired tables

Example:
The following command will overwrite `UsersTableSeeder.php` if it already exists in laravel's seeds directory.
```
php artisan iseed users --force
```

### clean
Optional parameter which will clean `app/database/seeds/DatabaseSeeder.php` before creating new seed class.

Example:
```
php artisan iseed users --clean
```

### database
Optional parameter which specifies the DB connection name.

Example:
```
php artisan iseed users --database=mysql2
```

### max
Optional parameter which defines the maximum number of entries seeded from a specified table. In case of multiple tables, limit will be applied to all of them.

Example:
```
artisan iseed users --max=10
```

### prerun
Optional parameter which assigns a laravel event name to be fired before seeding takes place. If an event listener returns `false`, seed will fail automatically.
You can assign multiple preruns for multiple table names by passing an array of comma separated DB names and respectively passing a comma separated array of prerun event names.

Example:
The following command will make a seed file which will fire an event named 'someEvent' before seeding takes place.
```
artisan iseed users --prerun=someEvent
```
The following example will assign `someUserEvent` to `users` table seed, and `someGroupEvent` to `groups` table seed, to be executed before seeding.
```
artisan iseed users,groups --prerun=someUserEvent,someGroupEvent
```
The following example will only assign a `someGroupEvent` to `groups` table seed, to be executed before seeding. Value for the users table prerun was omitted here, so `users` table seed will have no prerun event assigned.
```
artisan iseed users,groups --prerun=,someGroupEvent
```

### postrun
Optional parameter which assigns a laravel event name to be fired after seeding takes place. If an event listener returns `false`, seed will be executed, but an exception will be thrown that the postrun failed.
You can assign multiple postruns for multiple table names by passing an array of comma separated DB names and respectively passing a comma separated array of postrun event names.

Example:
The following command will make a seed file which will fire an event named 'someEvent' after seeding was completed.
```
artisan iseed users --postrun=someEvent
```
The following example will assign `someUserEvent` to `users` table seed, and `someGroupEvent` to `groups` table seed, to be executed after seeding.
```
artisan iseed users,groups --postrun=someUserEvent,someGroupEvent
```
The following example will only assign a `someGroupEvent` to `groups` table seed, to be executed after seeding. Value for the users table postrun was omitted here, so `users` table seed will have no postrun event assigned.
```
artisan iseed users,groups --postrun=,someGroupEvent
```

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

To (re)seed the database go to the Terminal and run Laravel's `db:seed command` (`php artisan db:seed`).

Please note that some users encountered a problem with large DB table exports ([error when seeding from table with many records](https://github.com/orangehill/iseed/issues/4)). The issue was solved by splitting input data into smaller chunks of elements per insert statement. As you may need to change the chunk size value in some extreme cases where DB table has a large number of columns, the chunk size is configurable in iSeed's `config.php` file:

	'chunk_size' => 500 // Maximum number of rows per insert statement

