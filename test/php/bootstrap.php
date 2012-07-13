<?php
// Load WordPress test environment
// https://github.com/nb/wordpress-tests


define('DEBIKI_PLUGIN_DIR', dirname(__FILE__).'/../../');

$path_to_wordpress_tests =
	DEBIKI_PLUGIN_DIR.'wordpress-tests/init.php';

#$path_to_wordpress_tests =
	#dirname(__FILE__).'/../../wordpress-tests/init.php';

if (file_exists($path_to_wordpress_tests)) {
    require_once $path_to_wordpress_tests;
} else {
    exit("Couldn't find $path_to_wordpress_tests\n");
}
