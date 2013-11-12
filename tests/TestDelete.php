<?php

use Bkwld\Croppa\Croppa;

class TestDelete extends PHPUnit_Framework_TestCase {

	public function dir() {
		return __DIR__.'/delete_files';
	}

	public function make() {
		return new Croppa(array(
			'src_dirs' => array($this->dir()),
			'max_crops' => 12,
		));
	}

	public function testContainsSelf() {
		$relative = 'me.jpg';
		$absolute = $this->dir().'/'.$relative;
		$croppa = $this->make();
		$this->assertTrue(in_array($absolute, $croppa->findFilesToDelete($relative)));
	}

	// https://github.com/BKWLD/croppa/issues/32
	public function testContainsDoesNotContainSimilar() {
		$relative = 'me.jpg';
		$similar = $this->dir().'/me-and-you.jpg';
		$croppa = $this->make();
		$this->assertFalse(in_array($similar, $croppa->findFilesToDelete($relative)));
	}

	public function testLength() {
		$croppa = $this->make();
		$this->assertEquals(2, count($croppa->findFilesToDelete('me.jpg')));
	}

	public function testBadURL() {
		$croppa = $this->make();
		$this->assertEquals(0, count($croppa->findFilesToDelete('whatever.dude')));
	}

}