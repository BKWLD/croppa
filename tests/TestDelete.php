<?php

use Bkwld\Croppa\Helpers;
use Bkwld\Croppa\Storage;
use Bkwld\Croppa\URL;

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

	// https://github.com/BKWLD/croppa/issues/97
	public function testDeleteWithDashedName() {
		
		$disk = Mockery::mock('League\Flysystem\Filesystem')
			->shouldReceive('listContents')
			->withAnyArgs()
			->andReturn([
					['path' => '7up-33cl.png', 'basename' => '7up-33cl.png'],
					['path' => '7up-33cl-130x130.png', 'basename' => '7up-33cl-130x130.png'],
				])
			->shouldReceive('delete')
			->withAnyArgs()
			->times(2)
			->getMock();

		$storage = new Storage();
		$storage->setSrcDisk($disk);
		$storage->setCropsDisk($disk);
		$this->assertNull($storage->deleteSrc('7up-33cl.png'));
		$this->assertNull($storage->deleteCrops('7up-33cl.png'));

	}

	// Instantiate a helpers instance using mocked disks so the whole delete
	// logic can be checked
	private function mockHelpersForDeleting() {

		// The path is to a sub dir
		$url = new URL([
			'path' => 'uploads/(?:thumbs/)?(.*)$',
		]);

		$src = Mockery::mock('League\Flysystem\Filesystem')
			->shouldReceive('listContents')
			->withAnyArgs()
			->andReturn([
					['path' => 'me.jpg', 'basename' => 'me.jpg'],
					['path' => 'unrelated.jpg', 'basename' => 'unrelated.jpg'],
				])
			->shouldReceive('delete')
			->withAnyArgs()
			->once()
			->getMock();

		$crops = Mockery::mock('League\Flysystem\Filesystem')
			->shouldReceive('listContents')
			->withAnyArgs()
			->andReturn([
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
		$storage->setSrcDisk($src);
		$storage->setCropsDisk($crops);

		return new Helpers($url, $storage);

	}

	public function testDeleteCropsInSubDir() {
		$helpers = $this->mockHelpersForDeleting();
		$helpers->delete('/uploads/me.jpg');
	}

	public function testDeleteCropsInSubDirWithFullURL() {
		$helpers = $this->mockHelpersForDeleting();
		$helpers->delete('http://domain.com/uploads/me.jpg');
	}

	public function tearDown() {
		Mockery::close();
	}
}