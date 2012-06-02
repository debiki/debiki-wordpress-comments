<?php
/*
Plugin Name: Debiki for Wordpress
Plugin URI: http://wordpress.debiki.com
Description: A more efficient commenting system that hopefully contributes to fruitful discussions.
Version: 0.00.01
Author: Kaj Magnus Lindberg
Author URI: http://kajmagnus.debiki.se
License: GPLv2 or later
*/

add_filter('comments_template', 'path_to_debiki_comments');

function path_to_debiki_comments($comments) {
	return dirname(__FILE__) . '/comments.php';
}


add_action('wp_head', 'echo_debiki_head');

function echo_debiki_head() {
	$res = '/wp-content/plugins/debiki-wordpress/res/';
	echo "
    <meta name='viewport' content='initial-scale=1.0, minimum-scale=0.01'/>
	 <link rel='stylesheet' href='" . $res . "jquery-ui/jquery-ui-1.8.16.custom.css'>
	 <link rel='stylesheet' href='" . $res . "debiki.css'>
    <script src='http://cdnjs.cloudflare.com/ajax/libs/modernizr/2.5.3/modernizr.min.js'></script>
    <script src='https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js'></script>
    <script src='http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.19/jquery-ui.min.js'></script>
	 <script>
		// Play Framework 2's `require` and `exports` not available.
		window.require = function() {};
		window.exports = {};

		var debiki = { scriptLoad: $.Deferred() };
		Modernizr.load({
			test: Modernizr.touch,
			nope: [
			'" . $res . "jquery-scrollable.js',
			'" . $res . "debiki-utterscroll.js',
			'" . $res . "bootstrap-tooltip.js'],
			both: [
			'" . $res . "diff_match_patch.js',
			'" . $res . "html-sanitizer-bundle.js',
			'" . $res . "jquery-cookie.js',
			'" . $res . "tagdog.js',
			'" . $res . "javascript-yaml-parser.js',
			'" . $res . "debiki.js']
		});
		</script>
		";
}

?>
