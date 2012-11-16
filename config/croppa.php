<?php return array(
	
	// Directories to search for source files
	'src_dirs' => array(
		path('public'),
	),
	
	// Maximum number of sizes to allow for a particular
	// source file.  This is to limit scripts from filling
	// up your hard drive with images.  Set to 0 or comment
	// out to have no limit.
	'max_crops' => 6,
	
	//Set this to true to get an thumbnails dir in the dir 
	//where the file is. Don't forget to create this dir manually
	//with the correct permissions -> if you dont want to use it, leave it empty ''
	'custom_savepath_thumbnails' => 'thumbnails',
);