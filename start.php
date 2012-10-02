<?php

// Add dependencies to the Laravel autoloader
Autoloader::map(array(
    'Croppa' => Bundle::path('croppa').'library/Croppa.php',
    'PhpThumbFactory' => Bundle::path('croppa').'vendor/PHPThumb/src/ThumbLib.inc.php',
));

// Subscribe to 404 events, these are how Croppa gets triggered
Event::listen('404', function() {
	
	// Pass Croppa the current URL
	Croppa::handle_404(Request::uri());
});