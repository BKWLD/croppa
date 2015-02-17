<?php

use Bkwld\Croppa\Croppa;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\visitor\vfsStreamStructureVisitor;

class TestGeneration extends PHPUnit_Framework_TestCase {

	private $root;
	private $croppa;
	public function setUp() {

		// Make an image
		$gd = imagecreate(500, 400);
		ob_start();
		imagejpeg($gd);
		$this->src = ob_get_clean();

		// Make virtual fileystem
		$this->root = vfsStream::setup('dir', null, array(
			'uploads' => array(
				'00' => array(
					'file.jpg' => $this->src,
				)
			)
		));

		// Share a croppa instance
		$this->croppa = new Croppa(array(
			'public' => vfsStream::url('dir'),
			'src_dirs' => array(vfsStream::url('dir/uploads')),
		));
	}

	public function testPasthru() {
		$this->croppa->generate('/uploads/00/file-_x_.jpg');
		$size = getimagesize(vfsStream::url('dir/uploads/00/file-_x_.jpg'));
		$this->assertEquals('500x400', $size[0].'x'.$size[1]);
	}

	public function testWidthConstraint() {
		$this->croppa->generate('/uploads/00/file-200x_.jpg');
		$size = getimagesize(vfsStream::url('dir/uploads/00/file-200x_.jpg'));
		$this->assertEquals('200x160', $size[0].'x'.$size[1]);
	}

	public function testHeightConstraint() {
		$this->croppa->generate('/uploads/00/file-_x200.jpg');
		$size = getimagesize(vfsStream::url('dir/uploads/00/file-_x200.jpg'));
		$this->assertEquals('250x200', $size[0].'x'.$size[1]);
	}

	public function testWidthAndHeightConstraint() {
		$this->croppa->generate('/uploads/00/file-200x100.jpg');
		$size = getimagesize(vfsStream::url('dir/uploads/00/file-200x100.jpg'));
		$this->assertEquals('200x100', $size[0].'x'.$size[1]);
	}

	public function testWidthAndHeightResize() {
		$this->croppa->generate('/uploads/00/file-200x200-resize.jpg');
		$size = getimagesize(vfsStream::url('dir/uploads/00/file-200x200-resize.jpg'));
		$this->assertEquals('200x160', $size[0].'x'.$size[1]);
	}

	public function testWidthAndHeightTrim() {
		$this->croppa->generate('/uploads/00/file-200x200-trim_perc(0.25,0.25,0.75,0.75).jpg');
		$size = getimagesize(vfsStream::url('dir/uploads/00/file-200x200-trim_perc(0.25,0.25,0.75,0.75).jpg'));
		$this->assertEquals('200x200', $size[0].'x'.$size[1]);
	}

}