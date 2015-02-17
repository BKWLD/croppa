<?php

use Bkwld\Croppa\Croppa;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\visitor\vfsStreamStructureVisitor;

class TestCheckForFile extends PHPUnit_Framework_TestCase {

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
					'me-200x100.jpg' => $image,
				)
			)
		));

		// Share a croppa instance
		$this->croppa = new Croppa(array(
			'public' => vfsStream::url('dir'),
			'src_dirs' => array(vfsStream::url('dir/uploads'))
		));
	}

	public function testIfExistsByURL() {
		$this->assertEquals(
			vfsStream::url('dir/uploads/00/me-200x100.jpg'), 
			$this->croppa->checkForFile('/uploads/00/me-200x100.jpg')
		);
	}

	public function testIfDoesntExistsByURL() {
		$this->assertFalse($this->croppa->checkForFile('/uploads/00/me-200x200.jpg'));
	}

	public function testIfExistsByPath() {
		$this->assertEquals(
			vfsStream::url('dir/uploads/00/me-200x100.jpg'), 
			$this->croppa->checkForFileByPath('00/me-200x100.jpg')
		);
	}

	public function testIfDoesntExistsByPath() {
		$this->assertFalse($this->croppa->checkForFileByPath('00/me-200x200.jpg'));
	}

	public function testIfNoLeadingSlash() {
		$this->assertEquals(
			vfsStream::url('dir/uploads/00/me-200x100.jpg'), 
			$this->croppa->checkForFile('uploads/00/me-200x100.jpg')
		);
	}

}