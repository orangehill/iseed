<?php namespace Orangehill\Iseed;

use Illuminate\Filesystem\Filesystem;

class Iseed {

	public function __construct(Filesystem $filesystem = null)
	{
		$this->files = $filesystem ?: new Filesystem;
	}

	/**
	 * Generates a seed file.
	 * @param  string  $table
	 * @return void
	 * @throws Orangehill\Iseed\TableNotFoundException
	 */
	public function generateSeed($table)
	{
		// Check if table exists
		if (!$this->hasTable($table)) throw new TableNotFoundException("Table $table was not found.");

		// Get the data
		$data = $this->getData($table);

		// Repack the data
		$dataArray = $this->repackSeedData($data);

		// Generate class name
		$className = $this->generateClassName($table);

		// Get template for a seed file contents
		$stub = $this->files->get($this->getStubPath() . '/seed.stub');

		// Get a seed folder path
		$seedPath = $this->getSeedPath();

		// Get a app/database/seeds path
		$seedsPath = $this->getPath($className, $seedPath);

		// Get a populated stub file
		$seedContent = $this->populateStub($className, $stub, $table, $dataArray);

		// Save a populated stub
		$this->files->put($seedsPath, $seedContent);

		// Update the DatabaseSeeder.php file
		return $this->updateDatabaseSeederRunMethod($className) !== false;

        return false;

	}

	/**
	 * Get a seed folder path
	 * @return string
	 */
	public function getSeedPath()
	{
		return app_path() . \Config::get('iseed::path');
	}

	/**
	 * Get the Data
	 * @param  string $table
	 * @return Array
	 */
	public function getData($table)
	{
		return \DB::table($table)->get();
	}

	/**
	 * Repacks data read from the database
	 * @param  object $data
	 * @return array
	 */
	public function repackSeedData($data)
	{
    	$dataArray = array();
    	if(is_array($data)) {
			foreach ($data as $row) {
				$rowArray = array();
				foreach($row as $columnName => $columnValue) {
					 $rowArray[$columnName] = $columnValue;
				}
				$dataArray[] = $rowArray;
			}
    	}
		return $dataArray;
	}

	/**
	 * Checks if a database table exists
	 * @return boolean
	 */
	public function hasTable($table)
	{
		return \Schema::hasTable($table);
	}

	/**
	 * Generates a seed class name (also used as a filename)
	 * @param  string  $table
	 * @return string
	 */
	public function generateClassName($table)
	{
		return ucfirst($table) . 'TableSeeder';
	}

	/**
	 * Get the path to the stub file.
	 * @return string
	 */
	public function getStubPath()
	{
		return __DIR__ . '/Stubs';
	}

	/**
	 * Populate the place-holders in the seed stub.
	 * @param  string  $class
	 * @param  string  $stub
	 * @param  string  $table
	 * @param  string  $data
	 * @return string
	 */
	public function populateStub($class, $stub, $table, $data)
	{
        $inserts = '';
        $chunks = array_chunk($data, \Config::get('iseed::chunk_size'));
        foreach ($chunks as $chunk) {
            $inserts .= sprintf("\n\t\t\DB::table('%s')->insert(%s);", $table, $this->prettifyArray($chunk));
        }
        
		$stub = str_replace('{{class}}', $class, $stub);

		if (!is_null($table)) {
			$stub = str_replace('{{table}}', $table, $stub);
		}

		$stub = str_replace('{{insert_statements}}', $inserts, $stub);

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
	protected function prettifyArray($array)
	{
		$content = var_export($array, true);
		$content = str_replace("  ", "\t", $content);
		$content = str_replace("\n", "\n\t\t", $content);
		return $content;
	}

    /**
    * Updates the DatabaseSeeder file's run method (kudoz to: https://github.com/JeffreyWay/Laravel-4-Generators)
    * @param  string  $className
    * @return bool
    */
    public function updateDatabaseSeederRunMethod($className)
    {
    	$databaseSeederPath = app_path() . \Config::get('iseed::path') . '/DatabaseSeeder.php';

        $content = $this->files->get($databaseSeederPath);
        if(strpos($content, '$this->call(\'UsersTableSeeder\')')===false)
			$content = preg_replace("/(run\(\).+?)}/us", "$1\t\$this->call('{$className}');\n\t}", $content);

        return $this->files->put($databaseSeederPath, $content) !== false;
        return false;
    }

}