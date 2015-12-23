<?php namespace Orangehill\Iseed;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class IseedCommand extends Command {

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
	public function fire()
	{
		// if clean option is checked empty iSeed template in DatabaseSeeder.php
		if($this->option('clean')) {
			app('iseed')->cleanSection();
		}

		$tables = explode(",", $this->argument('tables'));
		$chunkSize = intval($this->option('max'));
		$exColumns = explode(",", $this->option('exColumns'));

		if($chunkSize < 1) {
			$chunkSize = null;
		}

		foreach ($tables as $table) {
			$table = trim($table);

			// generate file and class name based on name of the table
			list($fileName, $className) = $this->generateFileName($table);

			// if file does not exist or force option is turned on generate seeder
			if(!\File::exists($fileName) || $this->option('force')) {
				$this->printResult(app('iseed')->generateSeed($table, $this->option('database'), $chunkSize, $exColumns), $table);
				continue;
			}

			if($this->confirm('File ' . $className . ' already exist. Do you wish to override it? [yes|no]')) {
				// if user said yes overwrite old seeder
				$this->printResult(app('iseed')->generateSeed($table, $this->option('database'), $chunkSize, $exColumns), $table);
			}
		}

		return;
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('tables', InputArgument::REQUIRED, 'comma separated string of table names'),
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
			array('exColumns', null, InputOption::VALUE_OPTIONAL, 'exclude columns', null),
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
        if ($successful)
        {
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
    protected function generateFileName($table)
    {
	    /*
	     * This check seems to break when you use a different environment as it happens before the connection is set
	     * I had to comment this out to get it working with connection other than the default one
	     */
    	if(!\Schema::hasTable($table)) {
    		throw new TableNotFoundException("Table $table was not found.");
    	}

		// Generate class name and file name
		$className = app('iseed')->generateClassName($table);
		$seedPath = base_path() . config('iseed::config.path');
		return [$seedPath . '/' . $className . '.php', $className . '.php'];

    }
}
