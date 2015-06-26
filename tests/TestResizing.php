<?php

use Bkwld\Croppa\Image;

class TestResizing extends PHPUnit_Framework_TestCase {

	private $src;

	public function setUp() {

		// Make an image
		$gd = imagecreate(500, 400);
		ob_start();
		imagejpeg($gd);
		$this->src = ob_get_clean();
	}

	public function testPassthru() {
		$image = new Image($this->src);
		$size = getimagesizefromstring($image->process(null, null)->get());
		$this->assertEquals('500x400', $size[0].'x'.$size[1]);
	}

	public function testWidthConstraint() {
		$image = new Image($this->src);
		$size = getimagesizefromstring($image->process(200, null)->get());
		$this->assertEquals('200x160', $size[0].'x'.$size[1]);
	}

	public function testHeightConstraint() {
		$image = new Image($this->src);
		$size = getimagesizefromstring($image->process(null, 200)->get());
		$this->assertEquals('250x200', $size[0].'x'.$size[1]);
	}

	public function testWidthAndHeightConstraint() {
		$image = new Image($this->src);
		$size = getimagesizefromstring($image->process(200, 100)->get());
		$this->assertEquals('200x100', $size[0].'x'.$size[1]);
	}

	public function testTooSmall() {
		$image = new Image($this->src);
		$size = getimagesizefromstring($image->process(600, 600)->get());
		$this->assertEquals('400x400', $size[0].'x'.$size[1]);
	}

	public function testNotWideEnough() {
		$image = new Image($this->src);
		$size = getimagesizefromstring($image->process(1000, 400)->get());
		$this->assertEquals('500x200', $size[0].'x'.$size[1]);
	}

	public function testNotTallEnough() {
		$image = new Image($this->src);
		$size = getimagesizefromstring($image->process(500, 500)->get());
		$this->assertEquals('400x400', $size[0].'x'.$size[1]);
	}

	public function testWidthAndHeightWithUpscale() {
		$image = new Image($this->src, ['resizeUp' => true]);
		$size = getimagesizefromstring($image->process(500, 500)->get());
		$this->assertEquals('500x500', $size[0].'x'.$size[1]);
	}

	public function testWidthAndHeightResize() {
		$image = new Image($this->src);
		$size = getimagesizefromstring($image->process(200, 200, ['resize' => null])->get());
		$this->assertEquals('200x160', $size[0].'x'.$size[1]);
	}

	public function testWidthAndHeightTrim() {
		$image = new Image($this->src);
		$size = getimagesizefromstring($image->process(200, 200, ['trim_perc' => [0.25,0.25,0.75,0.75]])->get());
		$this->assertEquals('200x200', $size[0].'x'.$size[1]);
	}

}