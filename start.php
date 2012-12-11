<?php

// Add dependencies to the Laravel autoloader
Autoloader::map(array(
    'Croppa' => Bundle::path('croppa').'library/Croppa.php',
    'Croppa\Exception' => Bundle::path('croppa').'library/Exception.php',
    'PhpThumbFactory' => Bundle::path('croppa').'vendor/PHPThumb/src/ThumbLib.inc.php',
));

// Pass along the Config data so Croppa is decoupled from Laravel
Croppa::config(Config::get('croppa::croppa'));

// Subscribe to 404 events, these are how Croppa gets triggered
Event::listen('404', function() {
	
	// Increase memory limit, cause some images require a lot
	// too resize
	ini_set('memory_limit', '128M');
	
	// Pass Croppa the current URL
	Croppa::handle_404(Request::uri());
});