<?php

use Bkwld\Croppa\Croppa;

class TestUrlGenerator extends PHPUnit_Framework_TestCase {

	public function testWidthAndHeight() {
		$croppa = new Croppa();
		$this->assertEquals('/path/file-200x100.png', $croppa->url('/path/file.png', 200, 100));
	}

	public function testIgnore() {
		$croppa = new Croppa(array(
			'ignore' => '.+\.gif$',
		));
		$this->assertEquals('/path/file.gif', $croppa->url('/path/file.gif', 200, 100));
		$this->assertEquals('/path/file-200x100.png', $croppa->url('/path/file.png', 200, 100));
	}

}