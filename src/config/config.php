<?php
return [
    /**
     * Path where the seeders will be generated
     * The default is /database/seeds
     */
    'path' => '/database/seeds',

    /**
     * Path where the Seeder is
     * The default is /database/seeds/DatabaseSeeder.php
     */
    'seeder_path' => '/database/seeds/DatabaseSeeder.php',

    /**
     * Whether the Seeder should be modified after running the iseed command
     * The default is true
     */
    'seeder_modification' => true,

    /**
     * Maximum number of rows per insert statement
     */
    'chunk_size' => 500,

    /**
     * You may alternatively set an absolute path to a custom stub file
     * The default stub file is located in /vendor/orangehill/iseed/src/Orangehill/Iseed/Stubs/seed.stub
     */
    'stub_path' => false,

    /**
     * You may customize the line that preceeds the inserts inside the seeder
     * You MUST keep both %s however, the first will be replaced by the table name and the second by the inserts themselves
     * The default is \DB::table('%s')->insert(%s);
     */
    'insert_command' => "\DB::table('%s')->insert(%s)",
];
