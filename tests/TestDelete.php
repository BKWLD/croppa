<?php

use Bkwld\Croppa\Croppa;
use org\bovigo\vfs\vfsStream;

class TestDelete extends PHPUnit_Framework_TestCase {

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
			'me.jpg' => $image,
			'me-200x100.jpg' => $image,
			'me-and-you.jpg' => $image,
		));

		// Share a croppa instance
		$this->croppa = new Croppa(array(
			'src_dirs' => array(vfsStream::url('dir')),
			'max_crops' => 12,
		));
	}

	public function testContainsSelf() {
		$this->assertContains(vfsStream::url('dir/me.jpg'), $this->croppa->findFilesToDelete('me.jpg'));
	}

	// https://github.com/BKWLD/croppa/issues/32
	public function testContainsDoesNotContainSimilar() {
		$this->assertNotContains(vfsStream::url('dir/me-and-you.jpg'), $this->croppa->findFilesToDelete('me.jpg'));
	}

	public function testLength() {
		$this->assertEquals(2, count($this->croppa->findFilesToDelete('me.jpg')));
	}

	public function testBadURL() {
		$this->assertEquals(0, count($this->croppa->findFilesToDelete('whatever.dude')));
	}

	// https://github.com/BKWLD/croppa/issues/48
	public function testFilenameWithDimensions() {
		$this->assertEquals(array(vfsStream::url('dir/me-200x100.jpg')), $this->croppa->findFilesToDelete('me-200x100.jpg'));
	}

}