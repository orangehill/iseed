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
	 * @return void
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
		$tables = explode(',', $this->argument('tables'));

		foreach ($tables as $table) {
			$this->printResult(app('iseed')->generateSeed($table,$this->option('database')), $table);
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

			array('tables', InputArgument::REQUIRED, 'table name'),
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
			array('database', \Config::get('database.default'), InputOption::VALUE_OPTIONAL, 'database connection', null),
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
			return $this->info("Created a seed file from table {$table}");
		}

		$this->error("Could not create seed file from table {$table}");
	}

}
