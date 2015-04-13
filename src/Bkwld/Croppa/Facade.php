<?php namespace Bkwld\Croppa;
class Facade extends \Illuminate\Support\Facades\Facade {
	protected static function getFacadeAccessor() { return 'Bkwld\Croppa\Helpers'; }
}
