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

		// generate file and class name based on name of the table
		list($fileName, $className) = $this->generateFileName($this->argument('table'));

		// if file does not exist generate seeder
		if(!\File::exists($fileName)) {
			$this->printResult(app('iseed')->generateSeed($this->argument('table')), $this->argument('table'));
			return;
		}
			
		// if seeder exist check wether should be overwriten
		if(!$this->confirm('File ' . $className . ' already exist. Do you wish to override it? [yes|no]')) {
			return;
		}

		// if user said yes overwrite old seeder
		$this->printResult(app('iseed')->generateSeed($this->argument('table')), $this->argument('table'));
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('table', InputArgument::REQUIRED, 'table name'),
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
    	if(!\Schema::hasTable($table)) {
    		throw new TableNotFoundException("Table $table was not found.");
    	}

		// Generate class name and file name
		$className = app('iseed')->generateClassName($this->argument('table'));
		$seedPath = app_path() . \Config::get('iseed::path');
		return [$seedPath . '/' . $className . '.php', $className . '.php'];

    }
}
