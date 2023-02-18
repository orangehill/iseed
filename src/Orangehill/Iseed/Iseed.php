<?php

namespace Orangehill\Iseed;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Iseed
{
    /**
     * Name of the database upon which the seed will be executed.
     */
    protected string $databaseName;

    /**
     * New line character for seed files.
     * Double quotes are mandatory!
     */
    private string $newLineCharacter = PHP_EOL;

    /**
     * Desired indent for the code.
     * For tabulator use \t
     * Double quotes are mandatory!
     */
    private string $indentCharacter = '    ';

    private readonly Composer $composer;

    private Filesystem        $files;

    public function __construct(Filesystem $filesystem = null, Composer $composer = null)
    {
        $this->files = $filesystem ?: new Filesystem;
        $this->composer = $composer ?: new Composer($this->files);
    }

    public function readStubFile($file): string
    {
        $buffer = file($file, FILE_IGNORE_NEW_LINES);

        return implode(PHP_EOL, $buffer);
    }

    /**
     * Generates a seed file.
     *
     * @param  int  $max
     * @param  null  $exclude
     * @param  null  $prerunEvent
     * @param  null  $postrunEvent
     */
    public function generateSeed(string $table, string $prefix = null, string $suffix = null, string $database = null, int|string $max = 0, int $chunkSize = 0, $exclude = null, $prerunEvent = null, $postrunEvent = null, bool $dumpAuto = true, bool $indexed = true, ?string $orderBy = null, string $direction = 'ASC'): bool
    {
        if (! $database) {
            $database = config('database.default');
        }

        $this->databaseName = $database;

        // Check if table exists
        if (! $this->hasTable($table)) {
            throw new TableNotFoundException("Table $table was not found.");
        }

        // Get the data
        $data = $this->getData($table, $max, $exclude, $orderBy, $direction);

        // Repack the data
        $dataArray = $this->repackSeedData($data);

        // Generate class name
        $className = $this->generateClassName($table, $prefix, $suffix);

        // Get template for a seed file contents
        $stub = $this->readStubFile($this->getStubPath().'/seed.stub');

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
            $indexed
        );

        // Save a populated stub
        $this->files->put($seedsPath, $seedContent);

        // Run composer dump-auto
        if ($dumpAuto) {
            $this->composer->dumpAutoloads();
        }

        // Update the DatabaseSeeder.php file
        return $this->updateDatabaseSeederRunMethod($className) !== false;
    }

    /**
     * Get a seed folder path
     *
     * @return string
     */
    public function getSeedPath()
    {
        return base_path().config('iseed::config.path');
    }

    /**
     * Get the Data
     *
     * @param  int  $max
     */
    public function getData(string $table, int|string $max, ?array $exclude = null, ?string $orderBy = null, ?string $direction = 'ASC'): array
    {
        $result = DB::connection($this->databaseName)->table($table);

        if (! empty($exclude)) {
            $allColumns = DB::connection($this->databaseName)->getSchemaBuilder()->getColumnListing($table);
            $result = $result->select(array_diff($allColumns, $exclude));
        }

        if ($orderBy) {
            $result = $result->orderBy($orderBy, $direction);
        }

        if ($max) {
            $result = $result->limit($max);
        }

        return $result->get()->toArray();
    }

    /**
     * Repacks data read from the database
     */
    public function repackSeedData(object|array $data): array
    {
        if (! is_array($data)) {
            $data = $data->toArray();
        }
        $dataArray = [];
        if (! empty($data)) {
            foreach ($data as $row) {
                $rowArray = [];
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
     */
    public function hasTable(string $table): bool
    {
        return Schema::connection($this->databaseName)->hasTable($table);
    }

    /**
     * Generates a seed class name (also used as a filename)
     */
    public function generateClassName(string $table, string $prefix = null, string $suffix = null): string
    {
        $tableString = '';
        $tableName = explode('_', $table);
        foreach ($tableName as $tableNameExploded) {
            $tableString .= ucfirst($tableNameExploded);
        }

        return ($prefix ?: '').ucfirst($tableString).'Table'.($suffix ?: '').'Seeder';
    }

    /**
     * Get the path to the stub file.
     */
    public function getStubPath(): string
    {
        return __DIR__.DIRECTORY_SEPARATOR.'stubs';
    }

    /**
     * Populate the place-holders in the seed stub.
     *
     * @param  null|string  $prerunEvent
     * @param  null|string  $postrunEvent
     */
    public function populateStub(string $class, string $stub, string $table, array $data, int $chunkSize = null, string|null $prerunEvent = null, string|null $postrunEvent = null, bool $indexed = true): string
    {
        $chunkSize = $chunkSize ?: config('iseed::config.chunk_size');

        $inserts = '';
        $chunks = array_chunk($data, $chunkSize);
        foreach ($chunks as $chunk) {
            $this->addNewLines($inserts);
            $this->addIndent($inserts, 2);
            $inserts .= sprintf(
                "\DB::table('%s')->insert(%s);",
                $table,
                $this->prettifyArray($chunk, $indexed)
            );
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
            '{{prerun_event}}', $prerunEventInsert, $stub
        );

        $stub = str_replace('{{table}}', $table, $stub);

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
            '{{postrun_event}}', $postrunEventInsert, $stub
        );

        return str_replace('{{insert_statements}}', $inserts, $stub);
    }

    /**
     * Create the full path name to the seed file.
     */
    public function getPath(string $name, string $path): string
    {
        return $path.'/'.$name.'.php';
    }

    /**
     * Prettify a var_export of an array
     */
    protected function prettifyArray(array $array, bool $indexed = true): string
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
            if (str_contains($lines[$i], ')')) {
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
                } //check string open/end
                elseif ($lines[$i][$j] == '\'') {
                    $inString = ! $inString;
                }
            }

            //check for openning bracket
            if (str_contains($lines[$i], '(')) {
                $tabCount++;
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Adds new lines to the passed content variable reference.
     */
    private function addNewLines(string &$content, int $numberOfLines = 1): void
    {
        while ($numberOfLines > 0) {
            $content .= $this->newLineCharacter;
            $numberOfLines--;
        }
    }

    /**
     * Adds indentation to the passed content reference.
     */
    private function addIndent(string &$content, int $numberOfIndents = 1): void
    {
        while ($numberOfIndents > 0) {
            $content .= $this->indentCharacter;
            $numberOfIndents--;
        }
    }

    /**
     * Cleans the iSeed section
     *
     * @throws FileNotFoundException
     */
    public function cleanSection(): bool
    {
        $databaseSeederPath = base_path().config('iseed::config.path').'/DatabaseSeeder.php';

        $content = $this->files->get($databaseSeederPath);

        $content = preg_replace("/(\#iseed_start.+?)\#iseed_end/us", "#iseed_start\n\t\t#iseed_end", (string) $content);

        return $this->files->put($databaseSeederPath, $content) !== false;
    }

    /**
     * Updates the DatabaseSeeder file's run method (kudoz to: https://github.com/JeffreyWay/Laravel-4-Generators)
     */
    public function updateDatabaseSeederRunMethod(string $className): bool
    {
        $databaseSeederPath = base_path().config('iseed::config.path').'/DatabaseSeeder.php';

        $content = (string) $this->files->get($databaseSeederPath);
        if (! str_contains((string) $content, "\$this->call({$className}::class)")) {
            if (
                strpos($content, '#iseed_start') &&
                strpos($content, '#iseed_end') &&
                strpos($content, '#iseed_start') < strpos($content, '#iseed_end')
            ) {
                $content = preg_replace("/(\#iseed_start.+?)(\#iseed_end)/us", "$1\$this->call({$className}::class);{$this->newLineCharacter}{$this->indentCharacter}{$this->indentCharacter}$2", (string) $content);
            } else {
                $content = preg_replace("/(run\(\).+?)}/us", "$1{$this->indentCharacter}\$this->call({$className}::class);{$this->newLineCharacter}{$this->indentCharacter}}", (string) $content);
            }
        }

        return $this->files->put($databaseSeederPath, $content) !== false;
    }
}
