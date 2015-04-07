<?php

use Bkwld\Croppa\Storage;
use Mockery;

class TestTooManyCrops extends PHPUnit_Framework_TestCase {

	private $app;
	private $dir;
	
	public function setUp() {

		// Mock the IoC container
		$this->app = Mockery::mock('Illuminate\Container\Container')
			->shouldReceive('bound')
			->andReturn(false)
			->getMock();

		// Mock flysystem
		$this->dir = Mockery::mock('League\Flysystem\Filesystem')
			->shouldReceive('listContents')
			->withAnyArgs()
			->andReturn([
					['basename' => 'me.jpg'],
					['basename' => 'me-200x100.jpg'],
					['basename' => 'me-200x200.jpg'],
					['basename' => 'me-200x300.jpg'],
					['basename' => 'unrelated.jpg'],
				])
			->getMock();

	}

	public function testAcceptableNumber() {
		$storage = new Storage($this->app, [
			'max_crops' => 4,
		]);
		$storage->setCropsDisk($this->dir);
		$this->assertFalse($storage->tooManyCrops('me.jpg'));
	}

	public function testTooMany() {
		$storage = new Storage($this->app, [
			'max_crops' => 3,
		]);
		$storage->setCropsDisk($this->dir);
		$this->assertTrue($storage->tooManyCrops('me.jpg'));
	}
}