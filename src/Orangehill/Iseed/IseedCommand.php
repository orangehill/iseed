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

        foreach ($this->argument('table') as $table) {
            $this->printResult(app('iseed')->generateSeed($table), $table);
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
			array('table', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'table names'),
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

}
