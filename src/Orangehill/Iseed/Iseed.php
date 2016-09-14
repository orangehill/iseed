<?php

namespace Orangehill\Iseed;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;

class Iseed {
	/**
	 * Name of the database upon which the seed will be executed.
	 *
	 * @var string
	 */
	protected $databaseName;

	/**
	 * New line character for seed files.
	 * Double quotes are mandatory!
	 *
	 * @var string
	 */
	private $newLineCharacter = "\r\n";

	/**
	 * Desired indent for the code.
	 * For tabulator use \t
	 * Double quotes are mandatory!
	 *
	 * @var string
	 */
	private $indentCharacter = "    ";

	public function __construct(Filesystem $filesystem = null) {
		$this->files = $filesystem ?: new Filesystem;
	}

	/**
	 * Generates a seed file.
	 * @param  string   $table
	 * @param  string   $database
	 * @param  int      $max
	 * @param  string   $prerunEvent
	 * @param  string   $postunEvent
	 * @return bool
	 * @throws Orangehill\Iseed\TableNotFoundException
	 */
	public function generateSeed($table, $database = null, $max = 0, $prerunEvent = null, $postrunEvent = null) {
		if (!$database) {
			$database = config('database.default');
		}

		$this->databaseName = $database;

		// Check if table exists
		if (!$this->hasTable($table)) {
			throw new TableNotFoundException("Table $table was not found.");
		}

		// Get the data
		$data = $this->getData($table, $max);

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
		$seedContent = $this->populateStub(
			$className,
			$stub,
			$table,
			$dataArray,
			null,
			$prerunEvent,
			$postrunEvent
		);

		// Save a populated stub
		$this->files->put($seedsPath, $seedContent);

		// Update the DatabaseSeeder.php file
		return $this->updateDatabaseSeederRunMethod($className) !== false;
	}

	/**
	 * Get a seed folder path
	 * @return string
	 */
	public function getSeedPath() {
		return base_path() . config('iseed::config.path');
	}

	/**
	 * Get the Data
	 * @param  string $table
	 * @return Array
	 */
	public function getData($table, $max) {
		if (!$max) {
			return \DB::connection($this->databaseName)->table($table)->get();
		}

		return \DB::connection($this->databaseName)->table($table)->limit($max)->get();
	}

	/**
	 * Repacks data read from the database
	 * @param  array|object $data
	 * @return array
	 */
	public function repackSeedData($data) {
		if (!is_array($data)) {
			$data = $data->toArray();
		}
		$dataArray = array();
		if (!empty($data)) {
			foreach ($data as $row) {
				$rowArray = array();
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
	 * @param string $table
	 * @return boolean
	 */
	public function hasTable($table) {
		return \Schema::connection($this->databaseName)->hasTable($table);
	}

	/**
	 * Generates a seed class name (also used as a filename)
	 * @param  string  $table
	 * @return string
	 */
	public function generateClassName($table) {
		$tableString = '';
		$tableName = explode('_', $table);
		foreach ($tableName as $tableNameExploded) {
			$tableString .= ucfirst($tableNameExploded);
		}
		return ucfirst($tableString) . 'TableSeeder';
	}

	/**
	 * Get the path to the stub file.
	 * @return string
	 */
	public function getStubPath() {
		return __DIR__ . DIRECTORY_SEPARATOR . 'Stubs';
	}

	/**
	 * Populate the place-holders in the seed stub.
	 * @param  string   $class
	 * @param  string   $stub
	 * @param  string   $table
	 * @param  string   $data
	 * @param  int      $chunkSize
	 * @param  string   $prerunEvent
	 * @param  string   $postunEvent
	 * @return string
	 */
	public function populateStub($class, $stub, $table, $data, $chunkSize = null, $prerunEvent = null, $postrunEvent = null) {
		$chunkSize = $chunkSize ?: config('iseed::config.chunk_size');
		$inserts = '';
		$chunks = array_chunk($data, $chunkSize);
		foreach ($chunks as $chunk) {
			$this->addNewLines($inserts);
			$this->addIndent($inserts, 2);
			$inserts .= sprintf(
				"\DB::table('%s')->insert(%s);",
				$table,
				$this->prettifyArray($chunk)
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

		if (!is_null($table)) {
			$stub = str_replace('{{table}}', $table, $stub);
		}

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

		$stub = str_replace('{{insert_statements}}', $inserts, $stub);

		return $stub;
	}

	/**
	 * Create the full path name to the seed file.
	 * @param  string  $name
	 * @param  string  $path
	 * @return string
	 */
	public function getPath($name, $path) {
		return $path . '/' . $name . '.php';
	}

	/**
	 * Prettify a var_export of an array
	 * @param  array  $array
	 * @return string
	 */
	protected function prettifyArray($array) {
		$content = var_export($array, true);

		$lines = explode("\n", $content);

		$inString = false;
		$tabCount = 3;
		for ($i = 1; $i < count($lines); $i++) {
			$lines[$i] = ltrim($lines[$i]);

			//Check for closing bracket
			if (strpos($lines[$i], ')') !== false) {
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
				}
				//check string open/end
				else if ($lines[$i][$j] == '\'') {
					$inString = !$inString;
				}
			}

			//check for openning bracket
			if (strpos($lines[$i], '(') !== false) {
				$tabCount++;
			}
		}

		$content = implode("\n", $lines);

		return $content;
	}

	/**
	 * Adds new lines to the passed content variable reference.
	 *
	 * @param string    $content
	 * @param int       $numberOfLines
	 */
	private function addNewLines(&$content, $numberOfLines = 1) {
		while ($numberOfLines > 0) {
			$content .= $this->newLineCharacter;
			$numberOfLines--;
		}
	}

	/**
	 * Adds indentation to the passed content reference.
	 *
	 * @param string    $content
	 * @param int       $numberOfIndents
	 */
	private function addIndent(&$content, $numberOfIndents = 1) {
		while ($numberOfIndents > 0) {
			$content .= $this->indentCharacter;
			$numberOfIndents--;
		}
	}

	/**
	 * Cleans the iSeed section
	 * @return bool
	 */
	public function cleanSection() {
		$databaseSeederPath = base_path() . config('iseed::config.path') . '/DatabaseSeeder.php';

		$content = $this->files->get($databaseSeederPath);

		$content = preg_replace("/(\#iseed_start.+?)\#iseed_end/us", "#iseed_start\n\t\t#iseed_end", $content);

		return $this->files->put($databaseSeederPath, $content) !== false;
		return false;
	}

	/**
	 * Updates the DatabaseSeeder file's run method (kudoz to: https://github.com/JeffreyWay/Laravel-4-Generators)
	 * @param  string  $className
	 * @return bool
	 */
	public function updateDatabaseSeederRunMethod($className) {
		$databaseSeederPath = base_path() . config('iseed::config.path') . '/DatabaseSeeder.php';

		$content = $this->files->get($databaseSeederPath);
		if (strpos($content, "\$this->call('{$className}')") === false) {
			if (
				strpos($content, '#iseed_start') &&
				strpos($content, '#iseed_end') &&
				strpos($content, '#iseed_start') < strpos($content, '#iseed_end')
			) {
				$content = preg_replace("/(\#iseed_start.+?)(\#iseed_end)/us", "$1\$this->call('{$className}');{$this->newLineCharacter}{$this->indentCharacter}{$this->indentCharacter}$2", $content);
			} else {
				$content = preg_replace("/(run\(\).+?)}/us", "$1{$this->indentCharacter}\$this->call('{$className}');{$this->newLineCharacter}{$this->indentCharacter}}", $content);
			}
		}

		return $this->files->put($databaseSeederPath, $content) !== false;
	}
}
