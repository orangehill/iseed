<?php

namespace Orangehill\Iseed;

use Illuminate\Console\Command;
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
     * Create a new command instance.
     *
     * @return \Orangehill\Iseed\IseedCommand
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        return $this->fire();
    }

    /**
     * Execute the console command (for <= 5.4).
     *
     * @return void
     */
    
     public function fire()
     {
         // Retrieve the tables argument. Since we want to allow a default of "all tables",
         // make sure the tables argument is optional in getArguments() (see below).
         $tablesArg = $this->argument('tables');
     
         if (empty($tablesArg)) {
             // Get all table names from the database
             $tables = app('iseed')->getAllTableNames();
         } else {
             // Otherwise, split the provided comma-separated table names
             $tables = explode(',', $tablesArg);
         }
     
         // Convert other options as needed
         $max = intval($this->option('max'));
         $chunkSize = intval($this->option('chunksize'));
         $exclude = explode(",", $this->option('exclude'));
         $prerunEvents = explode(",", $this->option('prerun'));
         $postrunEvents = explode(",", $this->option('postrun'));
         $dumpAuto = intval($this->option('dumpauto'));
         $indexed = !$this->option('noindex');
         $orderBy = $this->option('orderby');
         $direction = $this->option('direction');
         $prefix = $this->option('classnameprefix');
         $suffix = $this->option('classnamesuffix');
         $whereClause = $this->option('where');
     
         if ($max < 1) {
             $max = null;
         }
         if ($chunkSize < 1) {
             $chunkSize = null;
         }
     
         $tableIncrement = 0;
         foreach ($tables as $table) {
             $table = trim($table);
             $prerunEvent = isset($prerunEvents[$tableIncrement]) ? trim($prerunEvents[$tableIncrement]) : null;
             $postrunEvent = isset($postrunEvents[$tableIncrement]) ? trim($postrunEvents[$tableIncrement]) : null;
             $tableIncrement++;
     
             // generate file and class name based on name of the table
             list($fileName, $className) = $this->generateFileName($table, $prefix, $suffix);
     
             // if file does not exist or force option is turned on, generate seeder
             if (!\File::exists($fileName) || $this->option('force')) {
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
                         $direction,
                         $whereClause
                     ),
                     $table
                 );
                 continue;
             }
     
             if ($this->confirm('File ' . $className . ' already exists. Do you wish to override it? [yes|no]')) {
                 // Overwrite old seeder if confirmed
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
                         $direction,
                         $whereClause
                     ),
                     $table
                 );
             }
         }
     }
     

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('tables', InputArgument::OPTIONAL, 'comma separated string of table names'),
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('clean', null, InputOption::VALUE_NONE, 'clean iseed section', null),
            array('force', null, InputOption::VALUE_NONE, 'force overwrite of all existing seed classes', null),
            array('database', null, InputOption::VALUE_OPTIONAL, 'database connection', \Config::get('database.default')),
            array('max', null, InputOption::VALUE_OPTIONAL, 'max number of rows', null),
            array('chunksize', null, InputOption::VALUE_OPTIONAL, 'size of data chunks for each insert query', null),
            array('exclude', null, InputOption::VALUE_OPTIONAL, 'exclude columns', null),
            array('prerun', null, InputOption::VALUE_OPTIONAL, 'prerun event name', null),
            array('postrun', null, InputOption::VALUE_OPTIONAL, 'postrun event name', null),
            array('dumpauto', null, InputOption::VALUE_OPTIONAL, 'run composer dump-autoload', true),
            array('noindex', null, InputOption::VALUE_NONE, 'no indexing in the seed', null),
            array('orderby', null, InputOption::VALUE_OPTIONAL, 'orderby desc by column', null),
            array('direction', null, InputOption::VALUE_OPTIONAL, 'orderby direction', null),
            array('classnameprefix', null, InputOption::VALUE_OPTIONAL, 'prefix for class and file name', null),
            array('classnamesuffix', null, InputOption::VALUE_OPTIONAL, 'suffix for class and file name', null),
            array('where', null, InputOption::VALUE_OPTIONAL, 'where clause to filter records', null),
        );
    }

    /**
     * Provide user feedback, based on success or not.
     *
     * @param  boolean $successful
     * @param  string $table
     * @return void
     */
    protected function printResult($successful, $table)
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
     * @param  string $table
     * @return string
     */
    protected function generateFileName($table, $prefix=null, $suffix=null)
    {
        if (!\Schema::connection($this->option('database') ? $this->option('database') : config('database.default'))->hasTable($table)) {
            throw new TableNotFoundException("Table $table was not found.");
        }

        // Generate class name and file name
        $className = app('iseed')->generateClassName($table, $prefix, $suffix);
        $seedPath = base_path() . config('iseed::config.path');
        return [$seedPath . '/' . $className . '.php', $className . '.php'];
    }
}
