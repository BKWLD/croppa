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


	/*
	private $root;
	private $croppa;
	public function setUp() {

		// Make an image
		$gd = imagecreate(200, 100);
		ob_start();
		imagejpeg($gd);
		$image = ob_get_clean();

		// Make virtual fileystem
		$this->root = vfsStream::setup('dir', null, array(
			'uploads' => array(
				'00' => array(
					'me.jpg' => $image,
					'me-200x100.jpg' => $image,
					'me-and-you.jpg' => $image,
				)
			)
		));

		// Share a croppa instance
		$this->croppa = new Croppa(array(
			'public' => vfsStream::url('dir'),
			'src_dirs' => array(vfsStream::url('dir/uploads')),
		));
	}

	public function testContainsSelf() {
		$this->assertContains(
			vfsStream::url('dir/uploads/00/me.jpg'), 
			$this->croppa->findFilesToDelete('/uploads/00/me.jpg')
		);
	}

	// https://github.com/BKWLD/croppa/issues/32
	public function testContainsDoesNotContainSimilar() {
		$this->assertNotContains(
			vfsStream::url('dir/uploads/00/me-and-you.jpg'), 
			$this->croppa->findFilesToDelete('/uploads/00/me.jpg')
		);
	}

	public function testFindCrops() {
		$this->assertEquals(2, count($this->croppa->findFilesToDelete('/uploads/00/me.jpg')));
	}

	public function testNotOriginal() {
		$this->assertEquals(1, count($this->croppa->findFilesToDelete('/uploads/00/me.jpg', false)));
	}

	public function testBadURL() {
		$this->assertEquals(0, count($this->croppa->findFilesToDelete('/uploads/00/whatever.jpg')));
	}

	// https://github.com/BKWLD/croppa/issues/48
	public function testFilenameWithDimensions() {
		$this->assertEquals(
			array(vfsStream::url('dir/uploads/00/me-200x100.jpg')), 
			$this->croppa->findFilesToDelete('/uploads/00/me-200x100.jpg')
		);
	}
	*/
}