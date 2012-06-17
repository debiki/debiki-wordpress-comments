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



#===========================================================
# Constants and utilities
#===========================================================

function debiki_define_default($constant_name, $value) {
	if (! defined($constant_name))
		define($constant_name, $value);
}


define( 'DEBIKI_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
debiki_define_default( 'DEBIKI_SETTINGS_SLUG', 'debiki_comments_options' );
debiki_define_default( 'DEBIKI_ENABLED_QUERY_PARAM', 'debiki-comments-enabled' );


function debiki__( $text ) {
	return $text;  # later, something like: __( $text, 'debiki_comments_l10n' );
	       # also see:  http://codex.wordpress.org/Function_Reference/wp_localize_script
}


function debiki_comments_enabled() {
	$enabled_url_var = $_GET[DEBIKI_ENABLED_QUERY_PARAM];
	$enabled = !$enabled_url_var || $enabled_url_var == 'true';
	return $enabled;
}



#===========================================================
# Filters and action hooks
#===========================================================


# ===== Settings page

require dirname(__FILE__) . '/debiki-settings.php';

add_action('admin_menu', 'debiki_add_menu_items');

function debiki_add_menu_items() {
	add_options_page('Debiki Comments', 'Debiki Comments', 'manage_options',
			DEBIKI_SETTINGS_SLUG, 'debiki_echo_settings_page');
}

add_filter('plugin_action_links', 'debiki_add_plugin_action_link', 10, 2);

function debiki_add_plugin_action_link($links, $file) {
	if ($file != DEBIKI_PLUGIN_BASENAME)
		return $links;

	$settings_link = '<a href="' . menu_page_url(DEBIKI_SETTINGS_SLUG, false) . '">'
		. esc_html(debiki__('Settings')) . '</a>';
	array_unshift($links, $settings_link);
	return $links;
}


# ===== <html> elem classes

add_filter('language_attributes', 'classes_to_add_to_html_elem');

function classes_to_add_to_html_elem($output) {
	if (!debiki_comments_enabled())
		return $output;

	// `no-js` is for Modernizr.
	return $output . ' class="no-js dw-render-layout-pending dw-pri"';
}


# ===== Comments HTML

add_filter('comments_template', 'path_to_debiki_comments');

function path_to_debiki_comments($default_template) {
	if (!debiki_comments_enabled())
		return $default_template;

	return dirname(__FILE__) . '/comments.php';
}


# ===== Javascript and CSS

add_action('wp_head', 'echo_debiki_head');

function echo_debiki_head() {
	if (!debiki_comments_enabled())
		return;

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
		<style>
		.commentlist #respond {
			float: left;
			margin: 0 8em 0 0;
		}
		.commentlist {
			width: auto; /* override WP's 68.9% */
		}
		";

	# Tweak theme specific CSS to work with Debiki's layout and SVG arrows.
	# (Regrettably, Debiki won't work well with just any theme.)
	# Example: Twenty Eleven's background is gray, but Debiki's SVG arrows are
	# also gray and would be hard to notice against the background. It's not reasonable to
	# introduce additional colors, so instead, in the file
	# ./theme-specific/twenty-eleven-v-any.css, change the background to white.)
	$theme = wp_get_theme();
	$theme_name = $theme->get('Name');
	$theme_file = str_replace(' ', '-', $theme_name);
	$theme_file = strtolower($theme_file);
	$theme_file = dirname(__FILE__) . '/theme-specific/' . $theme_file;
	$theme_file_any_version = $theme_file . '-v-any.css';

	## In the future, perhaps match on theme version too, something reminiscient of this:
	#$theme_version = $theme->get('Version');
	#$theme_file_version_specific = $theme_file . '-v-' . $theme_version . '.css';
	#if (file_exists($theme_file_version_specific)) ... else ...

	if (file_exists($theme_file_any_version))
		require $theme_file_any_version;
	else
		require dirname(__FILE__) . '/theme-specific-default.css';
}

?>
