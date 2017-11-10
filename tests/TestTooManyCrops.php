<?php

use Bkwld\Croppa\Storage;
use PHPUnit\Framework\TestCase;

class TestTooManyCrops extends TestCase {

	private $dir;

	public function setUp() {

		// Mock flysystem
		$this->dir = Mockery::mock('League\Flysystem\Filesystem')
			->shouldReceive('listContents')
			->withAnyArgs()
			->andReturn([
					['path' => 'me.jpg', 'basename' => 'me.jpg'],
					['path' => 'me-too.jpg', 'basename' => 'me-too.jpg'],
					['path' => 'me-200x100.jpg', 'basename' => 'me-200x100.jpg'],
					['path' => 'me-200x200.jpg', 'basename' => 'me-200x200.jpg'],
					['path' => 'me-200x300.jpg', 'basename' => 'me-200x300.jpg'],
					['path' => 'unrelated.jpg', 'basename' => 'unrelated.jpg'],
				])
			->getMock();

	}

	public function testListCrops() {
		$storage = new Storage();
		$storage->setCropsDisk($this->dir);
		$this->assertEquals([
			'me-200x100.jpg',
			'me-200x200.jpg',
			'me-200x300.jpg',
		], $storage->listCrops('me.jpg'));
	}

	public function testAcceptableNumber() {
		$storage = new Storage(null, [ 'max_crops' => 4, ]);
		$storage->setCropsDisk($this->dir);
		$this->assertFalse($storage->tooManyCrops('me.jpg'));
	}

	public function testTooMany() {
		$storage = new Storage(null, [ 'max_crops' => 3, ]);
		$storage->setCropsDisk($this->dir);
		$this->assertTrue($storage->tooManyCrops('me.jpg'));
	}

	public function tearDown() {
		Mockery::close();
	}
}
