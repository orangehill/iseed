<?php

use Mockery as m;
use Illuminate\Database\Schema;

class IseedTest extends PHPUnit_Framework_TestCase {

    protected static $stubsDir, $testStubsDir;

    public function __construct()
    {
        static::$stubsDir = __DIR__.'/../src/Orangehill/Iseed/Stubs';
        static::$testStubsDir = __DIR__.'/Stubs';
    }

    public function tearDown()
    {
        m::close();
    }

    public function testPopulatesStub()
    {
        $productionStub = file_get_contents(static::$stubsDir . '/seed.stub');
        $iseed = new Orangehill\Iseed\Iseed();
        $output = $iseed->populateStub('test_class', $productionStub, 'test_table', 'test_data');
        $expected = file_get_contents(static::$testStubsDir . '/seed.stub');
        $this->assertEquals($expected, $output);
    }

    /**
     * @expectedException Orangehill\Iseed\TableNotFoundException
     * @expectedExceptionMessage Table nonexisting was not found.
     */
    public function testTableNotFoundException()
    {
        $hasTable = m::mock('Orangehill\Iseed\Iseed[hasTable]')->makePartial();
        $hasTable->shouldReceive('hasTable')->once()->andReturn(false);
        $hasTable->generateSeed('nonexisting');
    }

    public function testRepacksSeedData()
    {
        $data = array(
            array('id' => '1', 'name' => 'one'),
            array('id' => '2', 'name' => 'two')
        );
        $iseed = new Orangehill\Iseed\Iseed();
        $output = $iseed->repackSeedData($data);
        $this->assertEquals(json_encode($data), json_encode($output));
    }

    public function testCanGenerateClassName()
    {
        $iseed = new Orangehill\Iseed\Iseed();
        $output = $iseed->generateClassName('tablename');
        $this->assertEquals('TablenameTableSeeder', $output);
    }

    public function testCanGetStubPath()
    {
        $iseed = new Orangehill\Iseed\Iseed();
        $output = $iseed->getStubPath();
        $expected = substr(__DIR__, 0, -5) . 'src/Orangehill/Iseed/Stubs';
        $this->assertEquals($expected, $output);
    }

    public function testCanGenerateSeed()
    {
        $file = m::mock('Illuminate\Filesystem\Filesystem')->makePartial();
        $file->shouldReceive('get')
             ->once()
             ->with(substr(__DIR__, 0, -5) . 'src/Orangehill/Iseed/Stubs/seed.stub');
        $file->shouldReceive('put')
             ->once()
             ->with('seedPath', 'populatedStub');
        $mocked = m::mock('Orangehill\Iseed\Iseed', array($file))->makePartial();
        $mocked->shouldReceive('hasTable')->once()->andReturn(true);
        $mocked->shouldReceive('getData')->once()->andReturn(array());
        $mocked->shouldReceive('generateClassName')->once()->andReturn('ClassName');
        $mocked->shouldReceive('getSeedPath')->once()->andReturn('seedPath');
        $mocked->shouldReceive('getPath')->once()->with('ClassName', 'seedPath')->andReturn('seedPath');
        $mocked->shouldReceive('populateStub')->once()->andReturn('populatedStub');
        $mocked->shouldReceive('updateDatabaseSeederRunMethod')->once()->with('ClassName')->andReturn(true);
        $mocked->generateSeed('tablename');
    }

}
