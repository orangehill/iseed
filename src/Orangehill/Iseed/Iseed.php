<?php namespace Orangehill\Iseed;

use Illuminate\Filesystem\Filesystem;

class Iseed {

	public function __construct()
	{
		$this->files = new Filesystem;
	}

	/**
	 * Generates a seed file.
	 *
	 * @param  string  $table
	 * @return void
	 * @throws Orangehill\Iseed\TableNotFoundException
	 */
	public function generateSeed($table)
	{

		// Check if table exists
		if (!\Schema::hasTable($table)) throw new TableNotFoundException("Table $table was not found.");

		// Get the data
		$data = \DB::table($table)->get();

        	$dataArray = array();
        
		foreach ($data as $row)
		{
			$rowArray = array();
			foreach($row as $columnName => $columnValue)
			{
				 $rowArray[$columnName] = $columnValue;
			}
			$dataArray[] = $rowArray;
		}

		// Generate class name
		$className = $this->generateClassName($table);

		// Get template for a seed file contents
		$stub = $this->files->get($this->getStubPath() . '/seed.stub');

		// Get a seed folder path
		$seedPath = app_path() . \Config::get('iseed::path');

		// Save a populated stub
		$this->files->put($this->getPath($className, $seedPath), $this->populateStub($className, $stub, $table, $this->prettifyArray($dataArray)));

		// Update the DatabaseSeeder.php file
		$this->updateDatabaseSeederRunMethod($className);

	}

	/**
	 * Generates a seed class name (also used as a filename)
	 *
	 * @param  string  $table
	 * @return string
	 */
	protected function generateClassName($table)
	{
		return ucfirst($table) . 'TableSeeder';
	}

	/**
	 * Get the path to the stub file.
	 *
	 * @return string
	 */
	protected function getStubPath()
	{
		return __DIR__ . '/Stubs';
	}

	/**
	 * Populate the place-holders in the seed stub.
	 *
	 * @param  string  $class
	 * @param  string  $stub
	 * @param  string  $table
	 * @param  string  $data
	 * @return string
	 */
	protected function populateStub($class, $stub, $table, $data)
	{
		$stub = str_replace('{{class}}', $class, $stub);

		if ( ! is_null($table))
		{
			$stub = str_replace('{{table}}', $table, $stub);
		}

		if ( ! is_null($data))
		{
			$stub = str_replace('{{data}}', $data, $stub);
		}

		return $stub;
	}

	/**
	 * Create the full path name to the seed file.
	 *
	 * @param  string  $name
	 * @param  string  $path
	 * @return string
	 */
	protected function getPath($name, $path)
	{
		return $path . '/' . $name . '.php';
	}

	/**
	 * Prettify a var_export of an array
	 *
	 * @param  array  $array
	 * @return string
	 */
	protected function prettifyArray($array)
	{
		$content = var_export($array, true);
		$content = str_replace("  ", "\t", $content);
		$content = str_replace("\n", "\n\t\t", $content);
		return $content;
	}

    /**
    * Updates the DatabaseSeeder file's run method (kudoz to: https://github.com/JeffreyWay/Laravel-4-Generators)
    *
    * @param  string  $className
    * @return void
    */
    protected function updateDatabaseSeederRunMethod($className)
    {
    	$databaseSeederPath = app_path() . \Config::get('iseed::path') . '/DatabaseSeeder.php';

        $content = $this->files->get($databaseSeederPath);
        if(strpos($content, '$this->call(\'UsersTableSeeder\')')===false)
			$content = preg_replace("/(run\(\).+?)}/us", "$1\t\$this->call('{$className}');\n\t}", $content);

        $this->files->put($databaseSeederPath, $content);
    }

}
