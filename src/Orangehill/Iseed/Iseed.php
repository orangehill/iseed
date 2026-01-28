<?php

namespace Orangehill\Iseed;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class Iseed
{
    /**
     * Name of the database upon which the seed will be executed.
     *
     * @var string
     */
    protected $databaseName;

    /**
     * New line character for seed files.
     * Double quotes are mandatory!
     *
     * @var string
     */
    private $newLineCharacter = PHP_EOL;

    /**
     * Desired indent for the code.
     * For tabulator use \t
     * Double quotes are mandatory!
     *
     * @var string
     */
    private $indentCharacter = "    ";

    /**
     * @var Filesystem
     */
    private $files;

    /**
     * @var Composer
     */
    private $composer;

    public function __construct(Filesystem $filesystem = null, Composer $composer = null)
    {
        $this->files = $filesystem ?: new Filesystem;
        $this->composer = $composer ?: new Composer($this->files);
    }

    public function readStubFile($file)
    {
        $buffer = file($file, FILE_IGNORE_NEW_LINES);
        return implode(PHP_EOL, $buffer);
    }

    /**
     * Generates a seed file.
     * @param  string   $table
     * @param  string   $prefix
     * @param  string   $suffix
     * @param  string   $database
     * @param  int      $max
     * @param  string   $prerunEvent
     * @param  string   $postrunEvent
     * @param  bool     $register
     * @param  bool     $skipFkChecks
     * @param  int      $skip
     * @return bool
     * @throws Orangehill\Iseed\TableNotFoundException
     */
    public function generateSeed($table, $prefix = null, $suffix = null, $database = null, $max = 0, $chunkSize = 0, $exclude = null, $prerunEvent = null, $postrunEvent = null, $dumpAuto = true, $indexed = true, $orderBy = null, $direction = 'ASC', $whereClause = null, $register = true, $skipFkChecks = false, $skip = null)
    {
        if (!$database) {
            $database = config('database.default');
        }

        $this->databaseName = $database;

        // Check if table exists
        if (!$this->hasTable($table)) {
            throw new TableNotFoundException("Table $table was not found.");
        }

        // Get the data
        $data = $this->getData($table, $max, $exclude, $orderBy, $direction, $whereClause, $skip);

        // Repack the data
        $dataArray = $this->repackSeedData($data);

        // Generate class name
        $className = $this->generateClassName($table, $prefix, $suffix);

        // Get template for a seed file contents
        $stub = $this->readStubFile($this->getStubPath() . '/seed.stub');

        // Get a seed folder path
        $seedPath = $this->getSeedPath();

        // Get a app/database/seeds path
        $seedsPath = $this->getPath($className, $seedPath);

        // Get a populated stub file
        $seedContent = $this->populateStub(
            $className,
            $stub,
            $table,
            $dataArray,
            $chunkSize,
            $prerunEvent,
            $postrunEvent,
            $indexed,
            $database,
            $skipFkChecks
        );

        // Save a populated stub
        $this->files->put($seedsPath, $seedContent);

        // Run composer dump-auto
        if ($dumpAuto) {
            $this->composer->dumpAutoloads();
        }

        // Update the DatabaseSeeder.php file (unless --noregister is used)
        if ($register) {
            return $this->updateDatabaseSeederRunMethod($className) !== false;
        }

        return true;
    }

    /**
     * Get a seed folder path
     * @return string
     */
    public function getSeedPath()
    {
        return base_path() . config('iseed::config.path');
    }

    /**
     * Get the Data
     * @param  string $table
     * @return Array
     */
    public function getData($table, $max, $exclude = null, $orderBy = null, $direction = 'ASC', $whereClause = null, $skip = null)
    {
        $result = \DB::connection($this->databaseName)->table($table);

        if (!empty($exclude)) {
            $allColumns = \DB::connection($this->databaseName)->getSchemaBuilder()->getColumnListing($table);
            $result = $result->select(array_diff($allColumns, $exclude));
        }

        if ($whereClause) {
            $result = $result->whereRaw($whereClause);
        }

        if ($orderBy) {
            $result = $result->orderBy($orderBy, $direction);
        }

        if ($skip) {
            $result = $result->skip($skip);
        }

        if ($max) {
            $result = $result->limit($max);
        }

        return $result->get();
    }

    /**
     * Repacks data read from the database
     * @param  array|object $data
     * @return array
     */
    public function repackSeedData($data)
    {
        if (!is_array($data)) {
            $data = $data->toArray();
        }
        $dataArray = array();
        if (!empty($data)) {
            foreach ($data as $row) {
                $rowArray = array();
                foreach ($row as $columnName => $columnValue) {
                    $rowArray[$columnName] = $columnValue;
                }
                $dataArray[] = $rowArray;
            }
        }
        return $dataArray;
    }

    /**
     * Checks if a database table exists
     * @param string $table
     * @return boolean
     */
    public function hasTable($table)
    {
        return \Schema::connection($this->databaseName)->hasTable($table);
    }

    /**
     * Generates a seed class name (also used as a filename)
     * @param  string  $table
     * @param  string  $prefix
     * @param  string  $suffix
     * @return string
     */
    public function generateClassName($table, $prefix = null, $suffix = null)
    {
        $tableString = '';
        $tableName = explode('_', $table);
        foreach ($tableName as $tableNameExploded) {
            $tableString .= ucfirst($tableNameExploded);
        }
        return ($prefix ? $prefix : '') . ucfirst($tableString) . 'Table' . ($suffix ? $suffix : '') . 'Seeder';
    }

    /**
     * Get the path to the stub file.
     * @return string
     */
    public function getStubPath()
    {
        $customPath = null;
        try {
            $customPath = config('iseed::config.stub_path');
        } catch (\Exception $e) {
            // Config not available (e.g., in tests)
        }

        return $customPath ?: __DIR__ . DIRECTORY_SEPARATOR . 'stubs';
    }

    /**
     * Populate the place-holders in the seed stub.
     * @param  string   $class
     * @param  string   $stub
     * @param  string   $table
     * @param  string   $data
     * @param  int      $chunkSize
     * @param  string   $prerunEvent
     * @param  string   $postrunEvent
     * @param  bool     $indexed
     * @param  string   $database
     * @param  bool     $skipFkChecks
     * @return string
     */
    public function populateStub($class, $stub, $table, $data, $chunkSize = null, $prerunEvent = null, $postrunEvent = null, $indexed = true, $database = null, $skipFkChecks = false)
    {
        $chunkSize = $chunkSize ?: config('iseed::config.chunk_size');

        // Determine if we need to specify connection (only for non-default databases)
        $defaultDatabase = null;
        try {
            $defaultDatabase = config('database.default');
        } catch (\Exception $e) {
            // Config not available (e.g., in tests)
        }
        $useConnection = $database && $database !== $defaultDatabase;

        $inserts = '';
        $chunks = array_chunk($data, $chunkSize);
        foreach ($chunks as $chunk) {
            $this->addNewLines($inserts);
            $this->addIndent($inserts, 2);
            if ($useConnection) {
                $inserts .= sprintf(
                    "\\DB::connection('%s')->table('%s')->insert(%s);",
                    $database,
                    $table,
                    $this->prettifyArray($chunk, $indexed)
                );
            } else {
                $inserts .= sprintf(
                    "\\DB::table('%s')->insert(%s);",
                    $table,
                    $this->prettifyArray($chunk, $indexed)
                );
            }
        }

        $stub = str_replace('{{class}}', $class, $stub);

        $prerunEventInsert = '';
        if ($prerunEvent) {
            $prerunEventInsert .= "\$response = Event::until(new $prerunEvent());";
            $this->addNewLines($prerunEventInsert);
            $this->addIndent($prerunEventInsert, 2);
            $prerunEventInsert .= 'if ($response === false) {';
            $this->addNewLines($prerunEventInsert);
            $this->addIndent($prerunEventInsert, 3);
            $prerunEventInsert .= 'throw new Exception("Prerun event failed, seed wasn\'t executed!");';
            $this->addNewLines($prerunEventInsert);
            $this->addIndent($prerunEventInsert, 2);
            $prerunEventInsert .= '}';
        }

        $stub = str_replace(
            '{{prerun_event}}',
            $prerunEventInsert,
            $stub
        );

        if (!is_null($table)) {
            // Replace the DB::table statement (handles both default and non-default connections)
            if ($useConnection) {
                $stub = str_replace(
                    "\\DB::table('{{table}}')",
                    sprintf("\\DB::connection('%s')->table('%s')", $database, $table),
                    $stub
                );
            } else {
                $stub = str_replace('{{table}}', $table, $stub);
            }
        }

        $postrunEventInsert = '';
        if ($postrunEvent) {
            $postrunEventInsert .= "\$response = Event::until(new $postrunEvent());";
            $this->addNewLines($postrunEventInsert);
            $this->addIndent($postrunEventInsert, 2);
            $postrunEventInsert .= 'if ($response === false) {';
            $this->addNewLines($postrunEventInsert);
            $this->addIndent($postrunEventInsert, 3);
            $postrunEventInsert .= 'throw new Exception("Seed was executed but the postrun event failed!");';
            $this->addNewLines($postrunEventInsert);
            $this->addIndent($postrunEventInsert, 2);
            $postrunEventInsert .= '}';
        }

        $stub = str_replace(
            '{{postrun_event}}',
            $postrunEventInsert,
            $stub
        );

        $stub = str_replace('{{insert_statements}}', $inserts, $stub);

        // Add foreign key check disable/enable if requested
        if ($skipFkChecks) {
            // Find the delete statement and wrap everything with FK disable/enable
            $stub = preg_replace(
                '/(\\\\DB::.*->delete\(\);)/',
                "\\DB::statement('SET FOREIGN_KEY_CHECKS=0;');\n\n        $1",
                $stub
            );
            // Add enable statement after the last insert
            $stub = preg_replace(
                '/(->insert\([^;]+\);)(?!.*->insert\()/',
                "$1\n\n        \\DB::statement('SET FOREIGN_KEY_CHECKS=1;');",
                $stub
            );
        }

        return $stub;
    }

    /**
     * Create the full path name to the seed file.
     * @param  string  $name
     * @param  string  $path
     * @return string
     */
    public function getPath($name, $path)
    {
        return $path . '/' . $name . '.php';
    }

    /**
     * Prettify a var_export of an array
     * @param  array  $array
     * @return string
     */
    protected function prettifyArray($array, $indexed = true)
    {
        $content = ($indexed)
            ? var_export($array, true)
            : preg_replace("/[0-9]+ \=\>/i", '', var_export($array, true));

        $lines = explode("\n", $content);

        $inString = false;
        $tabCount = 3;
        for ($i = 1; $i < count($lines); $i++) {
            $lines[$i] = ltrim($lines[$i]);

            //Check for closing bracket
            if (strpos($lines[$i], ')') !== false) {
                $tabCount--;
            }

            //Insert tab count
            if ($inString === false) {
                for ($j = 0; $j < $tabCount; $j++) {
                    $lines[$i] = substr_replace($lines[$i], $this->indentCharacter, 0, 0);
                }
            }

            for ($j = 0; $j < strlen($lines[$i]); $j++) {
                //skip character right after an escape \
                if ($lines[$i][$j] == '\\') {
                    $j++;
                }
                //check string open/end
                else if ($lines[$i][$j] == '\'') {
                    $inString = !$inString;
                }
            }

            //check for opening bracket
            if (strpos($lines[$i], '(') !== false) {
                $tabCount++;
            }
        }

        $content = implode("\n", $lines);

        return $content;
    }

    /**
     * Adds new lines to the passed content variable reference.
     *
     * @param string    $content
     * @param int       $numberOfLines
     */
    private function addNewLines(&$content, $numberOfLines = 1)
    {
        while ($numberOfLines > 0) {
            $content .= $this->newLineCharacter;
            $numberOfLines--;
        }
    }

    /**
     * Adds indentation to the passed content reference.
     *
     * @param string    $content
     * @param int       $numberOfIndents
     */
    private function addIndent(&$content, $numberOfIndents = 1)
    {
        while ($numberOfIndents > 0) {
            $content .= $this->indentCharacter;
            $numberOfIndents--;
        }
    }

    /**
     * Cleans the iSeed section
     * @return bool
     */
    public function cleanSection()
    {
        $databaseSeederPath = base_path() . config('iseed::config.path') . '/DatabaseSeeder.php';

        $content = $this->files->get($databaseSeederPath);

        $content = preg_replace("/(\#iseed_start.+?)\#iseed_end/us", "#iseed_start\n\t\t#iseed_end", $content);

        return $this->files->put($databaseSeederPath, $content) !== false;
        return false;
    }

    /**
     * Updates the DatabaseSeeder file's run method (kudoz to: https://github.com/JeffreyWay/Laravel-4-Generators)
     * @param  string  $className
     * @return bool
     */
    public function updateDatabaseSeederRunMethod($className)
    {
        $databaseSeederPath = base_path() . config('iseed::config.path') . '/DatabaseSeeder.php';

        $content = $this->files->get($databaseSeederPath);
        if (strpos($content, "\$this->call({$className}::class)") === false) {
            if (
                strpos($content, '#iseed_start') &&
                strpos($content, '#iseed_end') &&
                strpos($content, '#iseed_start') < strpos($content, '#iseed_end')
            ) {
                $content = preg_replace("/(\#iseed_start.+?)(\#iseed_end)/us", "$1\$this->call({$className}::class);{$this->newLineCharacter}{$this->indentCharacter}{$this->indentCharacter}$2", $content);
            } else {
                $content = preg_replace("/(run\(\).+?)}/us", "$1{$this->indentCharacter}\$this->call({$className}::class);{$this->newLineCharacter}{$this->indentCharacter}}", $content);
            }
        }

        return $this->files->put($databaseSeederPath, $content) !== false;
    }

    /**
     * Get all table names
     *
     * @return array {string}
     */
    public function getAllTableNames(?string $databaseName = null): array
    {
        $this->setDatabaseName($databaseName);

        /* NOTE: see: https://github.com/laravel/framework/pull/48864
        * Depending on your Laravel version, you may use the Doctrine schema manager
        */

        $version = (int) substr(app()->version(), 0, 2);

        if ($version >= 11) {
            $connection = \DB::connection($this->databaseName);
            $tables = Schema::connection($this->databaseName)->getTables();
            $driver = $connection->getDriverName();

            // Filter tables based on database driver
            // MySQL/MariaDB: schema = database name, need to filter out other databases
            // SQLite: schema = 'main', only one database per file
            // PostgreSQL: schema = 'public' (default), filter by schema
            $schemaFilter = match ($driver) {
                'mysql', 'mariadb' => $connection->getDatabaseName(),
                'pgsql' => 'public',
                'sqlite' => 'main',
                default => null,
            };

            $collection = collect($tables);

            if ($schemaFilter !== null) {
                $collection = $collection->where('schema', $schemaFilter);
            }

            return $collection->pluck('name')->values()->toArray();
        }

        $schema = \DB::connection($this->databaseName)->getDoctrineSchemaManager();

        return $schema->listTableNames();
    }

    private function setDatabaseName(?string $databaseName)
    {
        $this->databaseName = $databaseName ?? config('database.default');
    }
}
