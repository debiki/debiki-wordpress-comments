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

# Twenty Eleven is a good default theme (I suppose since it's Automattics' latest one).
debiki_define_default( 'DEBIKI_DEFAULT_THEME', 'twentyeleven' );


function debiki__( $text ) {
	return $text;  # later, something like: __( $text, 'debiki_comments_l10n' );
	       # also see:  http://codex.wordpress.org/Function_Reference/wp_localize_script
}


function debiki_comments_enabled() {
	static $enabled = null;
	if (isset($enabled)) return $enabled;
	$enabled_url_var = $_GET[DEBIKI_ENABLED_QUERY_PARAM];
	$enabled_in_url = !isset($enabled_url_var) || $enabled_url_var == 'true';
	$enabled = $enabled_in_url;
	if (is_preview()) {
		$enabled_in_preview_opt = get_option('debiki_comments_enabled', 'true');
		$enabled_in_preview = $enabled_in_preview_opt == 'true';
		$enabled &= $enabled_in_preview;
	}
	return $enabled;
}



#===========================================================
# Theme specific files
#===========================================================

/**
 * Theme specific files are located in <debiki-plugin>/theme-specific/.
 * They are named e.g. twentyeleven-v0-comments.php, and -v0- means that the file
 * is for any Twenty Eleven theme version (well, from version 0 and upwards).
 */


/**
 * A prefix to prepend to 'style.css' or 'comments.php' -- the resulting file path
 * points to a theme specific file.
 *
 * Regrettably, Debiki won't work well with just any theme.
 * We need some theme specific tweaks, for the comments HTML and CSS to work with
 * Debiki's layout and SVG arrows.
 *
 * Example: Twenty Eleven's background is gray, but Debiki's SVG arrows are
 * also gray and would be hard to notice against the background. It's not reasonable to
 * introduce additional colors, so instead, in the file
 * ./theme-specific/twenty-eleven-v-any.css, change the background to white.)
 */
function debiki_theme_specific_file_path($which_file) {
	$theme = wp_get_theme();
	$prefix = dirname(__FILE__).'/theme-specific/';
	$suffix = '-v0-'.$which_file;
	$path = $prefix.$theme->get_template().$suffix;

	if (!file_exists($path))
		return $prefix.DEBIKI_DEFAULT_THEME.$suffix;

	return $path;

	## In the future, perhaps match on theme version too, something reminiscient of this:
	# $theme_version = $theme->get('Version');
	# $theme_file_version_specific = $theme_file . '-v' . $theme_version . '-style.css';
	# if (file_exists($theme_file_version_specific)) ... else ...
	## However, if we only need to upgrade ...-style.css but not ...-comments.php,
	## then we cannot use the same prefix for both -style.css and -comments.php.
}


function debiki_theme_specific_style_file() {
	return debiki_theme_specific_file_path('style.css');
}


function debiki_theme_specific_comments_template() {
	return debiki_theme_specific_file_path('comments.php');
}



#===========================================================
# Filters and action hooks
#===========================================================


# ===== Settings page

require dirname(__FILE__) . '/debiki-settings.php';

add_action('admin_menu', 'debiki_add_admin_menu_items');

function debiki_add_admin_menu_items() {
	add_options_page('Debiki Comments', 'Debiki Comments', 'manage_options',
			DEBIKI_SETTINGS_SLUG, 'debiki_echo_settings_page');
}

add_filter('plugin_action_links', 'debiki_plugin_action_links', 10, 2);

function debiki_plugin_action_links($links, $file) {
	if ($file != DEBIKI_PLUGIN_BASENAME)
		return $links;

	$settings_link = '<a href="' . menu_page_url(DEBIKI_SETTINGS_SLUG, false) . '">'
		. esc_html(debiki__('Settings')) . '</a>';
	array_unshift($links, $settings_link);
	return $links;
}


# ===== <html> elem classes

add_filter('language_attributes', 'debiki_classes_for_html_elem');

function debiki_classes_for_html_elem($output) {
	if (!debiki_comments_enabled())
		return $output;

	// `no-js` is for Modernizr.
	return $output . ' class="no-js dw-render-layout-pending dw-pri"';
}


# ===== Comment template

add_filter('comments_template', 'debiki_comments_template');

function debiki_comments_template($default_template) {
	if (!debiki_comments_enabled())
		return $default_template;

	return debiki_theme_specific_comments_template();
}

/**
 * Adds html class attributes required by Debiki's CSS and Javascript.
 */
class Debiki_Walker_Comment extends Walker_Comment {

	function start_lvl(&$output, $depth, $args) {
		$depth++;
		$GLOBALS['comment_depth'] = $depth;
		$is_root_reply = $depth === 1;
		$horiz_clearfix = $is_root_reply ? ' ui-helper-clearfix' : '';
		assert($args['style'] == 'ol');
		echo "<ol class='children dw-res{$horiz_clearfix}'>";
	}

}


# ===== Javascript and CSS

add_action('wp_head', 'debiki_echo_head');

function debiki_echo_head() {
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

	require debiki_theme_specific_style_file();
}


# ===== Theme preview options

add_action('customize_register', 'debiki_register_preview_control');

/**
 * Creates a theme preview option, `debiki_comments_enabled`, with value `true`
 * or `false`. Later, when the preview page is renedered (in the same HTTP
 * request), the option will be considered in `debiki_comments_enabled()`.
 */
function debiki_register_preview_control( $customize_manager ) {

	# See `register_controls()` in `wp-includes/class-wp-customize-manager.php`.

	# Concerning `priority` (below).
	# The theme preview config section with the highest number is placed at the end of
	# the config list. The last build in section is 'Static Front Page' with prio 120
	# as of Word Press 3.4.  Lets use a much higher prio, so in case WP adds more
	# sections, they'll appear before Debiki's section.
	$customize_manager->add_section( 'debiki_comments', array(
			'title'    => __( 'Debiki Comment System' ),
			'priority' => 300,
		));

	$customize_manager->add_setting( 'debiki_comments_enabled', array(
			'default' => 'true',
			'type' => 'option',
		));

	$customize_manager->add_control( 'debiki_comments_enabled', array(
			'label'   => debiki__( 'Debiki comments enabled' ),
			'section' => 'debiki_comments',
			'type'    => 'radio',
			'choices' => array(
				'true' => debiki__( 'Enabled' ),
				'false'  => debiki__( 'Disabled' ),
			),
		));
}


?>
