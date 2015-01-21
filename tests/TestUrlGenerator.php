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

	public function testNoWidthOrHeight() {
		$croppa = new Croppa();
		$this->assertEquals('/path/file.png', $croppa->url('/path/file.png'));
	}

	public function testNoWidth() {
		$croppa = new Croppa();
		$this->assertEquals('/path/file-_x100.png', $croppa->url('/path/file.png', null, 100));
	}

	public function testNoHeight() {
		$croppa = new Croppa();
		$this->assertEquals('/path/file-200x_.png', $croppa->url('/path/file.png', 200));
	}

	public function testHostInSrc() {
		$croppa = new Croppa();
		$this->assertEquals('/path/file-200x_.png', $croppa->url('http://domain.tld/path/file.png', 200));
		$this->assertEquals('/path/file-200x_.png', $croppa->url('https://domain.tld/path/file.png', 200));
	}

}