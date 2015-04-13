<?php

use Bkwld\Croppa\Storage;

class TestDelete extends PHPUnit_Framework_TestCase {

	public function testDeleteSrc() {
		
		$disk = Mockery::mock('League\Flysystem\Filesystem')
			->shouldReceive('listContents')
			->withAnyArgs()
			->andReturn([
					['path' => 'me.jpg', 'basename' => 'me.jpg'],
					['path' => 'me-200x100.jpg', 'basename' => 'me-200x100.jpg'],
					['path' => 'me-200x200.jpg', 'basename' => 'me-200x200.jpg'],
					['path' => 'me-200x300.jpg', 'basename' => 'me-200x300.jpg'],
					['path' => 'unrelated.jpg', 'basename' => 'unrelated.jpg'],
				])
			->shouldReceive('delete')
			->withAnyArgs()
			->once()
			->getMock();

		$storage = new Storage();
		$storage->setSrcDisk($disk);
		$this->assertNull($storage->deleteSrc('me.jpg'));
	}

	public function testDeleteCrops() {
		
		$disk = Mockery::mock('League\Flysystem\Filesystem')
			->shouldReceive('listContents')
			->withAnyArgs()
			->andReturn([
					['path' => 'me.jpg', 'basename' => 'me.jpg'],
					['path' => 'me-200x100.jpg', 'basename' => 'me-200x100.jpg'],
					['path' => 'me-200x200.jpg', 'basename' => 'me-200x200.jpg'],
					['path' => 'me-200x300.jpg', 'basename' => 'me-200x300.jpg'],
					['path' => 'unrelated.jpg', 'basename' => 'unrelated.jpg'],
				])
			->shouldReceive('delete')
			->withAnyArgs()
			->times(3)
			->getMock();

		$storage = new Storage();
		$storage->setCropsDisk($disk);
		$this->assertNull($storage->deleteCrops('me.jpg'));

	}
}