**Inverse seed generator (iSeed)** is a Laravel package that provides a method to generate a new seed file based on data from the existing database table.

[![Latest Stable Version](https://poser.pugx.org/orangehill/iseed/v/stable.svg)](https://packagist.org/packages/orangehill/iseed)
[![Total Downloads](https://poser.pugx.org/orangehill/iseed/downloads.svg)](https://packagist.org/packages/orangehill/iseed)
[![License](https://poser.pugx.org/orangehill/iseed/license.svg)](https://packagist.org/packages/orangehill/iseed)

**Supports Laravel 8, 9, 10, 11, 12, and 13** (PHP 8.0+)

## Installation

### 1. Require with [Composer](https://getcomposer.org/)
```sh
composer require orangehill/iseed
```

**Laravel 5.3.7 and below** or **Laravel 4** need specific version

```sh
composer require orangehill/iseed:2.2 # Laravel 5.3.7 and below
composer require orangehill/iseed:1.1 # Laravel 4
```

### 2. Add Service Provider (Laravel 5.4 and below)

Latest Laravel versions have auto discovery and automatically add service provider - if you're using 5.4.x and below, remember to add it to `providers` array at `/app/config/app.php`:

```php
// ...
Orangehill\Iseed\IseedServiceProvider::class,
```

## Artisan command options

### [table_name]
Optional. This parameter defines which table(s) will be used for seed creation.

If provided:
Use CSV notation to list one or more table names. A seed file will be generated for each specified table.

Examples Generate a seed file for a single table:
```
php artisan iseed my_table
```
Example Generate seed files for multiple tables:

```
php artisan iseed my_table,another_table
```

If omitted:
The command automatically retrieves all table names from your database and generates seeders for every table.
Examples:
```
php artisan iseed
```

### classnameprefix & classnamesuffix
Optionally specify a prefix or suffix for the Seeder class name and file name.
This is useful if you want to create an additional seed for a table that has an existing seed without overwriting the existing one.

Examples:

```
php artisan iseed my_table --classnameprefix=Customized
```
outputs CustomizedMyTableSeeder.php

```
php artisan iseed my_table,another_table --classnameprefix=Customized
```
outputs CustomizedMyTableSeeder.php and CustomizedAnotherTableSeeder.php

```
php artisan iseed my_table --classnamesuffix=Customizations
```
outputs MyTableCustomizationsSeeder.php

```
php artisan iseed my_table,another_table --classnamesuffix=Customizations
```
outputs MyTableCustomizationsSeeder.php and AnotherTableCustomizationsSeeder.php

### force
Optional parameter which is used to automatically overwrite any existing seeds for desired tables

Example:
The following command will overwrite `UsersTableSeeder.php` if it already exists in laravel's seeds directory.
```
php artisan iseed users --force
```

### dumpauto
Optional boolean parameter that controls the execution of `composer dump-autoload` command. Defaults to true.

Example that will stop `composer dump-autoload` from execution:
```
php artisan iseed users --dumpauto=false
```

### clean
Optional parameter which will clean `database/seeders/DatabaseSeeder.php` before creating new seed class.

Example:
```
php artisan iseed users --clean
```

### database
Optional parameter which specifies the DB connection name. When using a non-default connection, the generated seeder will include the connection specification (e.g., `\DB::connection('mysql2')->table(...)`) to ensure the seeder targets the correct database.

Example:
```
php artisan iseed users --database=mysql2
```

### max
Optional parameter which defines the maximum number of entries seeded from a specified table. In case of multiple tables, limit will be applied to all of them.

Example:
```
php artisan iseed users --max=10
```

### chunksize
Optional parameter which defines the size of data chunks for each insert query.

Example:
```
php artisan iseed users --chunksize=100
```

### orderby
Optional parameter which defines the column which will be used to order the results by, when used in conjunction with the max parameter that allows you to set the desired number of exported database entries.

Example:
```
artisan iseed users --max=10 --orderby=id
```

### direction
Optional parameter which allows you to set the direction of the ordering of results; used in conjunction with orderby parameter.

Example:
```
artisan iseed users --max=10 --orderby=id --direction=desc
```

### exclude
Optional parameter which accepts comma separated list of columns that you'd like to exclude from tables that are being exported. In case of multiple tables, exclusion will be applied to all of them.

Example:
```
php artisan iseed users --exclude=id
php artisan iseed users --exclude=id,created_at,updated_at
```

### prerun
Optional parameter which assigns a laravel event name to be fired before seeding takes place. If an event listener returns `false`, seed will fail automatically.
You can assign multiple preruns for multiple table names by passing an array of comma separated DB names and respectively passing a comma separated array of prerun event names.

Example:
The following command will make a seed file which will fire an event named 'someEvent' before seeding takes place.
```
php artisan iseed users --prerun=someEvent
```
The following example will assign `someUserEvent` to `users` table seed, and `someGroupEvent` to `groups` table seed, to be executed before seeding.
```
php artisan iseed users,groups --prerun=someUserEvent,someGroupEvent
```
The following example will only assign a `someGroupEvent` to `groups` table seed, to be executed before seeding. Value for the users table prerun was omitted here, so `users` table seed will have no prerun event assigned.
```
php artisan iseed users,groups --prerun=,someGroupEvent
```

### postrun
Optional parameter which assigns a laravel event name to be fired after seeding takes place. If an event listener returns `false`, seed will be executed, but an exception will be thrown that the postrun failed.
You can assign multiple postruns for multiple table names by passing an array of comma separated DB names and respectively passing a comma separated array of postrun event names.

Example:
The following command will make a seed file which will fire an event named 'someEvent' after seeding was completed.
```
php artisan iseed users --postrun=someEvent
```
The following example will assign `someUserEvent` to `users` table seed, and `someGroupEvent` to `groups` table seed, to be executed after seeding.
```
php artisan iseed users,groups --postrun=someUserEvent,someGroupEvent
```
The following example will only assign a `someGroupEvent` to `groups` table seed, to be executed after seeding. Value for the users table postrun was omitted here, so `users` table seed will have no postrun event assigned.
```
php artisan iseed users,groups --postrun=,someGroupEvent
```

### noindex
By using --noindex the seed can be generated as a non-indexed array.
The use case for this feature is when you need to merge two seed files.

Example:
```
php artisan iseed users --noindex
```

### noregister
By using --noregister the seed file will be generated but will not be added to `DatabaseSeeder.php`. This is useful when you want to create backup seeders or manually manage which seeders are registered.

Example:
```
php artisan iseed users --noregister
```

### skip-fk-checks
By using --skip-fk-checks the generated seeder will include statements to disable foreign key checks before deleting/inserting and re-enable them afterwards. This is useful when seeding tables that have foreign key constraints, as it prevents "foreign key constraint failed" errors.

Example:
```
php artisan iseed users --skip-fk-checks
```

**Note**: This option generates MySQL-specific `SET FOREIGN_KEY_CHECKS` statements.

### reset-sequences
By using --reset-sequences the generated seeder will include statements to reset PostgreSQL sequences after seeding. This prevents "duplicate key value violates unique constraint" errors that can occur when inserting new records after seeding, because PostgreSQL sequences don't automatically reset when using DELETE.

Example:
```
php artisan iseed users --reset-sequences
```

The generated seeder will include code to reset the sequence to the maximum ID value in the table:
```php
if (\DB::getDriverName() === 'pgsql') {
    \DB::statement("SELECT setval(pg_get_serial_sequence('users', 'id'), COALESCE((SELECT MAX(id) FROM users), 1))");
}
```

**Note**: This option generates PostgreSQL-specific statements and only executes when the database driver is PostgreSQL.

### skip
Optional parameter which defines the number of rows to skip before exporting. This is useful for paginating through large tables in combination with the `--max` option.

Example:
```
php artisan iseed users --skip=1000
```

When combined with `--max` for pagination:
```
php artisan iseed users --max=1000 --orderby=id
php artisan iseed users --max=1000 --skip=1000 --orderby=id
php artisan iseed users --max=1000 --skip=2000 --orderby=id
```

### where
Optional parameter which allows you to specify a SQL WHERE clause to filter the rows that will be included in the seed file. The WHERE clause should be provided as a string and will be applied directly to the SQL query.

Examples:
```sh
# Only seed users with example.com emails
php artisan iseed users --where="email LIKE '%@example.com'"

# Seed active users created after a specific date
php artisan iseed users --where="active = 1 AND created_at > '2024-01-01'"

# Combine with other options
php artisan iseed users --where="role = 'admin'" --max=10 --orderby=created_at --direction=desc
```

**Note**: When using complex WHERE clauses with special characters or spaces, make sure to properly escape and quote the condition string according to your shell's requirements.

## Usage

To generate a seed file for your users table simply call: `\Iseed::generateSeed('users', 'connectionName', 'numOfRows');`. `connectionName` and `numOfRows` are not required arguments.

This will create a file inside `/database/seeders`, with the contents similar to following example:

```php
<?php

namespace Database\Seeders;

// File: /database/seeders/UsersTableSeeder.php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{

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
                'id' => 1,
                'email' => 'admin@admin.com',
                'password' => '$2y$10$tUGCkQf/0NY3w1l9sobGsudt6UngnoVXx/lUoh9ElcSOD0ERRkK9C',
                'name' => 'Admin User',
                'created_at' => '2024-01-01 00:00:00',
                'updated_at' => '2024-01-01 00:00:00',
            ),
            1 =>
            array (
                'id' => 2,
                'email' => 'user@user.com',
                'password' => '$2y$10$ImNvsMzK/BOgNSYgpjs/3OjMKMHeA9BH/hjl43EiuBuLkZGPMuZ2W',
                'name' => 'Regular User',
                'created_at' => '2024-01-01 00:00:00',
                'updated_at' => '2024-01-01 00:00:00',
            ),
        ));
    }

}
```

This command will also update `/database/seeders/DatabaseSeeder.php` to include a call to this newly generated seed class.

If you wish you can define custom iSeed template in which all the calls will be placed. You can do this by using `#iseed_start` and `#iseed_end` templates anywhere within `/database/seeders/DatabaseSeeder.php`, for example:

```php
<?php

namespace Database\Seeders;

// File: /database/seeders/DatabaseSeeder.php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
        */
    public function run()
    {
        #iseed_start

        // All iSeed generated seeder calls will be placed here.

        #iseed_end
    }
}
```

Alternatively you can run Iseed from the command line using Artisan, e.g. `php artisan iseed users`. For generation of multiple seed files comma separated list of table names should be send as an argument for command, e.g. `php artisan iseed users,posts,groups`.

In case you try to generate seed file that already exists command will ask you a question whether you want to overwrite it or not. If you wish to overwrite it by default use `--force` Artisan Command Option, e.g. `php artisan iseed users --force`.

If you wish to clear iSeed template you can use Artisan Command Option `--clean`, e.g. `php artisan iseed users --clean`. This will clean template from `database/seeders/DatabaseSeeder.php` before creating new seed class.

You can specify db connection that will be used for creation of new seed files by using Artisan Command Option `--database=connection_name`, e.g. `php artisan iseed users --database=mysql2`.

To limit number of rows that will be exported from table use Artisan Command Option `--max=number_of_rows`, e.g. `php artisan iseed users --max=10`. If you use this option while exporting multiple tables specified limit will be applied to all of them.

To (re)seed the database go to the Terminal and run Laravel's `db:seed command` (`php artisan db:seed`).

Please note that some users encountered a problem with large DB table exports ([error when seeding from table with many records](https://github.com/orangehill/iseed/issues/4)). The issue was solved by splitting input data into smaller chunks of elements per insert statement. As you may need to change the chunk size value in some extreme cases where DB table has a large number of rows, the chunk size is configurable in iSeed's `config.php` file:

	'chunk_size' => 500 // Maximum number of rows per insert statement

## Custom Stub Template

You can customize the seed file template by creating a custom stub file and configuring iSeed to use it. Create a `config/iseed.php` file in your Laravel application:

```php
<?php
return [
    'stub_path' => resource_path('stubs'),
];
```

Then create your custom stub at `resources/stubs/seed.stub`. The stub supports the following placeholders:
- `{{class}}` - The seeder class name
- `{{table}}` - The database table name
- `{{insert_statements}}` - The generated insert statements
- `{{prerun_event}}` - Pre-run event code (if specified)
- `{{postrun_event}}` - Post-run event code (if specified)
