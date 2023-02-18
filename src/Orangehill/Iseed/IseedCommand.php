<?php

namespace Orangehill\Iseed;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class IseedCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'iseed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate seed file from table';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->fire();

        return 1;
    }

    /**
     * Execute the console command (for <= 5.4).
     */
    public function fire(): void
    {
        // if clean option is checked empty iSeed template in DatabaseSeeder.php
        if ($this->option('clean')) {
            app('iseed')->cleanSection();
        }

        $tables = explode(',', $this->argument('tables'));
        $max = intval($this->option('max'));
        $chunkSize = intval($this->option('chunksize'));
        $exclude = explode(',', $this->option('exclude'));
        $prerunEvents = explode(',', $this->option('prerun'));
        $postrunEvents = explode(',', $this->option('postrun'));
        $dumpAuto = intval($this->option('dumpauto'));
        $indexed = ! $this->option('noindex');
        $orderBy = $this->option('orderby');
        $direction = $this->option('direction');
        $prefix = $this->option('classnameprefix');
        $suffix = $this->option('classnamesuffix');

        if ($max < 1) {
            $max = null;
        }

        if ($chunkSize < 1) {
            $chunkSize = null;
        }

        $tableIncrement = 0;
        foreach ($tables as $table) {
            $table = trim($table);
            $prerunEvent = null;
            if (isset($prerunEvents[$tableIncrement])) {
                $prerunEvent = trim($prerunEvents[$tableIncrement]);
            }
            $postrunEvent = null;
            if (isset($postrunEvents[$tableIncrement])) {
                $postrunEvent = trim($postrunEvents[$tableIncrement]);
            }
            $tableIncrement++;

            // generate file and class name based on name of the table
            [$fileName, $className] = $this->generateFileName($table, $prefix, $suffix);

            // if file does not exist or force option is turned on generate seeder
            if (! File::exists($fileName) || $this->option('force')) {
                $this->printResult(
                    app('iseed')->generateSeed(
                        $table,
                        $prefix,
                        $suffix,
                        $this->option('database'),
                        $max,
                        $chunkSize,
                        $exclude,
                        $prerunEvent,
                        $postrunEvent,
                        $dumpAuto,
                        $indexed,
                        $orderBy,
                        $direction
                    ),
                    $table
                );

                continue;
            }

            if ($this->confirm('File '.$className.' already exist. Do you wish to override it? [yes|no]')) {
                // if user said yes overwrite old seeder
                $this->printResult(
                    app('iseed')->generateSeed(
                        $table,
                        $prefix,
                        $suffix,
                        $this->option('database'),
                        $max,
                        $chunkSize,
                        $exclude,
                        $prerunEvent,
                        $postrunEvent,
                        $dumpAuto,
                        $indexed
                    ),
                    $table
                );
            }
        }
    }

    /**
     * Get the console command arguments.
     */
    protected function getArguments(): array
    {
        return [
            ['tables', InputArgument::REQUIRED, 'comma separated string of table names'],
        ];
    }

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        return [
            ['clean', null, InputOption::VALUE_NONE, 'clean iseed section', null],
            ['force', null, InputOption::VALUE_NONE, 'force overwrite of all existing seed classes', null],
            ['database', null, InputOption::VALUE_OPTIONAL, 'database connection', Config::get('database.default')],
            ['max', null, InputOption::VALUE_OPTIONAL, 'max number of rows', null],
            ['chunksize', null, InputOption::VALUE_OPTIONAL, 'size of data chunks for each insert query', null],
            ['exclude', null, InputOption::VALUE_OPTIONAL, 'exclude columns', null],
            ['prerun', null, InputOption::VALUE_OPTIONAL, 'prerun event name', null],
            ['postrun', null, InputOption::VALUE_OPTIONAL, 'postrun event name', null],
            ['dumpauto', null, InputOption::VALUE_OPTIONAL, 'run composer dump-autoload', true],
            ['noindex', null, InputOption::VALUE_NONE, 'no indexing in the seed', null],
            ['orderby', null, InputOption::VALUE_OPTIONAL, 'orderby desc by column', null],
            ['direction', null, InputOption::VALUE_OPTIONAL, 'orderby direction', null],
            ['classnameprefix', null, InputOption::VALUE_OPTIONAL, 'prefix for class and file name', null],
            ['classnamesuffix', null, InputOption::VALUE_OPTIONAL, 'suffix for class and file name', null],
        ];
    }

    /**
     * Provide user feedback, based on success or not.
     *
     * @param  bool  $successful
     * @return void
     */
    protected function printResult(bool|int $successful, string $table)
    {
        if ($successful) {
            $this->info("Created a seed file from table {$table}");

            return;
        }

        $this->error("Could not create seed file from table {$table}");
    }

    /**
     * Generate file name, to be used in test wether seed file already exist
     *
     *
     * @return string
     */
    protected function generateFileName(string $table, ?string $prefix = null, ?string $suffix = null): array|string
    {
        if (! Schema::connection($this->option('database') ?: config('database.default'))->hasTable($table)) {
            throw new TableNotFoundException("Table $table was not found.");
        }

        // Generate class name and file name
        $className = app('iseed')->generateClassName($table, $prefix, $suffix);
        $seedPath = base_path().config('iseed::config.path');

        return [$seedPath.'/'.$className.'.php', $className.'.php'];
    }
}
