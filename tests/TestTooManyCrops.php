<?php

use Bkwld\Croppa\Croppa;
use org\bovigo\vfs\vfsStream;

class TestTooManyCrops extends PHPUnit_Framework_TestCase {

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
			'me-200x200.jpg' => $image,
			'me-200x300.jpg' => $image,
		));
		
	}

	public function testAcceptableNumber() {
		$croppa = new Croppa(array(
			'public' => vfsStream::url('dir'),
			'src_dirs' => array(vfsStream::url('dir')),
			'max_crops' => 3,
		));
		$this->assertFalse($croppa->tooManyCrops(vfsStream::url('dir/me.jpg')));
	}

	public function testTooMany() {
		$croppa = new Croppa(array(
			'public' => vfsStream::url('dir'),
			'src_dirs' => array(vfsStream::url('dir')),
			'max_crops' => 2,
		));
		$this->assertTrue($croppa->tooManyCrops(vfsStream::url('dir/me.jpg')));
	}
}