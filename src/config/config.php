<?php return array(
	
	// Directories to search for source files
	'src_dirs' => array(
		App::make('path.public'),
	),
	
	// Maximum number of sizes to allow for a particular
	// source file.  This is to limit scripts from filling
	// up your hard drive with images.  Set to false or comment
	// out to have no limit.
	'max_crops' => 12,

	// The jpeg quality of generated images.  The difference between
	// 100 and 95 usually cuts the file size in half.  Going down to
	// 70 looks ok on photos and will reduce filesize by more than another
	// half but on vector files there is noticeable aliasing.
	'jpeg_quality' => 95,

	// Turn on interlacing to make progessive jpegs
	'interlace' => true,
	
);