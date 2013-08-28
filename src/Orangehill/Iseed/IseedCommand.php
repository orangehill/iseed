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
		app('iseed')->generateSeed($this->argument('table'));
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

}