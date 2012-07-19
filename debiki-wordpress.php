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

/**
 * Copyright (c) 2012 Kaj Magnus Lindberg (born 1979)
 *
 * Parts Copyright 2011 by the contributors to WordPress and (?) b2,
 * (Search for "the contributors" in this file and you'll find more details.)
 */


namespace Debiki;


require_once('debiki-comment-ratings.php');
require_once('debiki-database.php');

$debiki_db = new Debiki_Database();

register_activation_hook(__FILE__, array(& $debiki_db, 'install'));
register_uninstall_hook(__FILE__, array(& $debiki_db, 'uninstall')); # UNTESTED
add_action('wpmu_new_blog',
		array(& $debiki_db, 'install_for_single_blog'), 10, 6);  # UNTESTED



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
debiki_define_default( 'DEBIKI_LAYOUT_QUERY_PARAM', 'debiki-layout' );

# Twenty Eleven is a good default theme (I suppose since it's Automattics' latest one).
debiki_define_default( 'DEBIKI_DEFAULT_THEME', 'default' );

debiki_define_default( 'DEBIKI_MODERNIZR_VERSION', '2.5.3');
debiki_define_default( 'DEBIKI_MODERNIZR_URL',
		'http://cdnjs.cloudflare.com/ajax/libs/modernizr/2.5.3/modernizr.min.js' );

debiki_define_default( 'DEBIKI_JQUERY_VERSION', '1.7.2');
debiki_define_default( 'DEBIKI_JQUERY_URL',
		'http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.js'); # MIN!

debiki_define_default( 'DEBIKI_JQUERY_UI_VERSION', '1.8.19'); # 1.8.21 available, not .20
debiki_define_default( 'DEBIKI_JQUERY_UI_URL',
		'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.19/jquery-ui.js'); # MIN!


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
# Admin stuff
#===========================================================


# ===== Settings page

require_once dirname(__FILE__) . '/debiki-settings.php';

add_action('admin_menu', '\Debiki\debiki_add_admin_menu_items');

function debiki_add_admin_menu_items() {
	add_options_page('Debiki Comments', 'Debiki Comments', 'manage_options',
			DEBIKI_SETTINGS_SLUG, 'debiki_echo_settings_page');
}

add_filter('plugin_action_links', '\Debiki\debiki_plugin_action_links', 10, 2);

function debiki_plugin_action_links($links, $file) {
	if ($file != DEBIKI_PLUGIN_BASENAME)
		return $links;

	$settings_link = '<a href="' . menu_page_url(DEBIKI_SETTINGS_SLUG, false) . '">'
		. esc_html(debiki__('Settings')) . '</a>';
	array_unshift($links, $settings_link);
	return $links;
}


# ===== Theme preview options

add_action('customize_register', '\Debiki\debiki_register_preview_control');

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


if (!debiki_comments_enabled())
	return;


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
function debiki_theme_specific_file_path($which_file, $only_file_name = false) {
	$templateToUse = $_GET[DEBIKI_LAYOUT_QUERY_PARAM];
	if (empty($templateToUse)) {
		$theme = wp_get_theme();
		$templateToUse = $theme->get_template();
	}
	$prefix = dirname(__FILE__).'/theme-specific/';
	$suffix = '-v0-'.$which_file;
	$path = $prefix.$templateToUse.$suffix;

	if (!file_exists($path))
		$path = $prefix.DEBIKI_DEFAULT_THEME.$suffix;

	if ($only_file_name)
		$path = str_replace($prefix, '', $path);

	return $path;

	## In the future, perhaps match on theme version too, something reminiscient of this:
	# $theme_version = $theme->get('Version');
	# $theme_file_version_specific = $theme_file . '-v' . $theme_version . '-style.css';
	# if (file_exists($theme_file_version_specific)) ... else ...
	## However, if we only need to upgrade ...-style.css but not ...-comments.php,
	## then we cannot use the same prefix for both -style.css and -comments.php.
}


function debiki_theme_specific_javascript_file_name() {
	return debiki_theme_specific_file_path('script.js', true);
}

function debiki_theme_specific_style_file_name() {
	return debiki_theme_specific_file_path('style.css', true);
}


function debiki_theme_specific_comments_template() {
	return debiki_theme_specific_file_path('comments.php');
}



#===========================================================
# Filters and action hooks
#===========================================================


# ===== <html> and <body> elem classes

add_filter('language_attributes',
		'\Debiki\debiki_add_modernizr_nojs_class_to_html_elem');

/**
 * Attempts to add `class="no-js"` to the <html> elem. This fails sometimes,
 * because some themes (e.g. Annotum Base) generates the <html> tag like so:
 * `<html class="no-js" <?php language_attributes() ? >>`
 * and then the browser (Chrome 19 at least) picks up only the first `class="no-js"`
 * attribute, but ignores the second one (written just below).
 * This is okay though, because what we want to do is precisely adding a 'no-js'
 * class. And the reason Annotum Base also does the same thing, is that Annotum Base
 * also (just like Debiki) uses Modernizr.
 */
function debiki_add_modernizr_nojs_class_to_html_elem($output) {
	if (!debiki_comments_enabled())
		return $output;

	// `no-js` is for Modernizr.
	return $output.' class="no-js" ';
}

add_filter('body_class', '\Debiki\debiki_body_classes');

/**
 * Adds Debiki specific classes to the <body> elem.
 */
function debiki_body_classes($classes) {
	if (!debiki_comments_enabled())
		return $output;

	// `no-js` is for Modernizr.
	$classes[] = 'dw-render-layout-pending';
	$classes[] = 'dw-pri';
	$classes[] = 'DW';
	return $classes;
}


# ===== Comment template

add_filter('comments_template', '\Debiki\debiki_comments_template');

function debiki_comments_template($default_template) {
	if (!debiki_comments_enabled())
		return $default_template;

	return debiki_theme_specific_comments_template();
}

function debiki_list_comments($callback) {
	global $debiki_db;
	$post_id = get_the_ID();
	$ratings = $debiki_db->load_comment_ratings_for_post($post_id);
   wp_list_comments(array(
			'walker' => new Debiki_Walker_Comment($ratings),
			'callback' => $callback,
			'style' => 'ol',
			'debiki_comment_ratings' => $ratings));
}


/**
 * Prints CSS comment classes, but without `alt`, `odd`, `even` and `depth-X` classes.
 *
 * Copied from `get_comment_class` in wp-includes/comment-template.php.
 *
 * This function only: Copyright 2011 by the contributors to WordPress and (?) b2,
 * see WordPress' licensing info, in <wordpress-base-dir>/license.txt.
 */
function debiki_comment_classes() {

	$comment = get_comment($comment_id);
	$classes = array();
	$classes[] = ( empty( $comment->comment_type ) ) ? 'comment' : $comment->comment_type;

	// If the comment author has an id (registered), then print the log in name
	if ( $comment->user_id > 0 && $user = get_userdata($comment->user_id) ) {
		// For all registered users, 'byuser'
		$classes[] = 'byuser';
		$classes[] = 'comment-author-' . sanitize_html_class(
				$user->user_nicename, $comment->user_id);

		// For comment authors who are the author of the post
		if ( $post = get_post($post_id) ) {
			if ( $comment->user_id === $post->post_author )
				$classes[] = 'bypostauthor';
		}
	}

	$classesStr = join(' ', $classes);
	return $classesStr;
}


/*
This doesn't work!!? Why not?

add_filter('comment_class', '\Debiki\debiki_comment_class_filter');

/**
 * Removes classes .alt, .odd, .even, and .thread-alt, -odd, -even.
 * They make little sense with Debiki's two dimensional layout (it doesn't look nice
 * with whole threads colored or every second item colored, and isn't needed
 * since we're using arrows instead to indicate comment parent child relationships.)
 *  /
function debiki_comment_class_filter($classes) {
	debiki_array_remove('alt', $classes);
	debiki_array_remove('odd', $classes);
	debiki_array_remove('even', $classes);
	debiki_array_remove('thread-alt', $classes);
	debiki_array_remove('thread-odd', $classes);
	debiki_array_remove('thread-even', $classes);
	return $classes;
}

function debiki_array_remove($elem, $array) {
	if (in_array($elem, $array))
		unset($array[array_search($elem)]);
}
*/


/**
 * Adds html class attributes required by Debiki's CSS and Javascript.
 */
class Debiki_Walker_Comment extends \Walker_Comment {

	var $comment_ratings;

	public function __construct($comment_ratings) {
		$this->comment_ratings = $comment_ratings;
	}


	function start_lvl(&$output, $depth, $args) {
		$depth++;
		$GLOBALS['comment_depth'] = $depth;
		$is_root_reply = $depth === 1;
		$horiz_clearfix = $is_root_reply ? ' ui-helper-clearfix' : '';
		assert($args['style'] == 'ol');
		echo "<ol class='children dw-res{$horiz_clearfix}'>";
	}


	/**
	 * Based on wp-includes/class-wp-walker.php, class Walker->paged_walk.
	 * This function: Parts Copyright 2011 by the contributors to WordPress and (?) b2.
	 */
	function paged_walk( $elements, $max_depth, $page_num, $per_page ) {

		if ( empty($elements) || $max_depth < -1 )
			return '';

		$args = array_slice( func_get_args(), 4 );
		$output = '';

		$id_field = $this->db_fields['id'];
		$parent_field = $this->db_fields['parent'];

		# Place blog article replies in $top_level_elements.
		# Place replies to reply X in $children_elements[X].
		$top_level_elements = array();
		$children_elements  = array();
		foreach ( $elements as $e) {
			if ( 0 == $e->$parent_field )
				$top_level_elements[] = $e;
			else
				$children_elements[ $e->$parent_field ][] = $e;
		}

		# Calculate sort scores.
		foreach ($elements as $e) {
			$e->sort_score =
					$this->comment_ratings->sort_score_for_comment($e->comment_ID);
		}

		# Sort interesting comments first, then sort by post date.
		$sort_func = function($comment_1, $comment_2) {
			if ($comment_2->sort_score < $comment_1->sort_score) return -1;
			if ($comment_1->sort_score < $comment_2->sort_score) return +1;
			if ($comment_2->comment_date_gmt < $comment_1->comment_date_gmt)
				return -1;
			if ($comment_1->comment_date_gmt < $comment_2->comment_date_gmt)
				return 1;
			return 0;
		};
		usort($top_level_elements, $sort_func);
		foreach ($children_elements as $parent => $children)
			usort($children, $sort_func);

		# WordPress normally loads comments by date asc. Here is how you can
		# sort them by date desc instead.
		/*
		if ( !empty($args[0]['reverse_top_level']) ) {
			$top_level_elements = array_reverse( $top_level_elements );
		}
		if ( !empty($args[0]['reverse_children']) ) {
			foreach ( $children_elements as $parent => $children )
				$children_elements[$parent] = array_reverse( $children );
		} */

		foreach ( $top_level_elements as $e ) {
			$this->display_element( $e, $children_elements, $max_depth, 0, $args, $output );
		}

		if ( count( $children_elements ) > 0 ) {
			$empty_array = array();
			foreach ( $children_elements as $orphans )
				foreach( $orphans as $op )
					$this->display_element( $op, $empty_array, 1, 0, $args, $output );
		}

		return $output;
	}
}


/**
 * Data attributes with comment and post ids that our Javascript can use
 * to find the comment and post ID, when moving the reply form. (Then two <input>s
 * with parent comment id and post id need to be updated.)
 */
function debiki_comment_data_attrs($comment, $opt_post = null) {

	# This should be safe w.r.t. xss attacks; `get_comment_reply_link` in
	# comment-template.php already inlines $comment->comment_ID and $post->ID
	# in the way I do below.
	$post_id = $opt_post ? $opt_post->ID : $comment->comment_post_ID;
	return
		" data-dw_wp_comment_id='$comment->comment_ID'" .
		" data-dw_wp_post_id='$post_id'";
}



# ===== Remove some WordPress Javascript

# Remove reply link on click Javascript. WordPress' built in removal and reapperance
# of the reply form happens instantly, and with Debiki's two dimensional layout it's
# hard to understand what is happening, if things are shuffled around in two
# dimensions, instantly. Instead, we'll use some jQuery animations to move the
# reply form more slowly and smoothly.

add_filter('comment_reply_link', '\Debiki\debiki_remove_reply_link_javascript');
add_filter('post_comments_link', '\Debiki\debiki_remove_reply_link_javascript');

function debiki_remove_reply_link_javascript($link) {
	# Replace: onclick='return addComment.moveForm(...)'
	# With: The empty string.
	# But also handle onclick="..." (that is, double quotes instead of single quotes).
	# ((Another approach is to remove the onclick handler via Javascript.
	# That might be better actually; that wouldn't break if WordPress renames some
	# related Javascript variable / function.
	# Currently, I do unbind onclick, search for `removeAttr('onclick')` in
	# twentyeleven-v0-script.js ))
	$link_without_onclick = preg_replace(
			"#onclick=(['\"])return addComment.moveForm\\([^)]+\\)\\1#", '', $link, 1);
	return $link_without_onclick;
}

# Also remove the Javascript file with the functions that the onclick handlers invoke.

add_action('init','\Debiki\debiki_deregister_comment_reply_script');


function debiki_deregister_comment_reply_script() {
	wp_deregister_script('comment-reply');
}



# ===== Debiki's Javascript and CSS


add_action('wp_enqueue_scripts', '\Debiki\debiki_enqueue_scripts');

function debiki_enqueue_scripts() {
	wp_deregister_script('modernizr');
	wp_register_script('modernizr', DEBIKI_MODERNIZR_URL, false, DEBIKI_MODERNIZR_VERSION);
	wp_enqueue_script('modernizr');

	wp_deregister_script('jquery');
	wp_register_script('jquery', DEBIKI_JQUERY_URL, false, DEBIKI_JQUERY_VERSION);
	wp_enqueue_script('jquery');

	wp_deregister_script('jquery-ui');
	wp_register_script('jquery-ui', DEBIKI_JQUERY_UI_URL, false, DEBIKI_JQUERY_UI_VERSION);
	wp_enqueue_script('jquery-ui');
}


add_action('wp_head', '\Debiki\debiki_echo_head');

function debiki_echo_head() {
	if (!debiki_comments_enabled())
		return;
	$res = plugin_dir_url(__FILE__).'res/';
	$theme_specific = plugin_dir_url(__FILE__).'theme-specific/';

	// Enable Utterscroll even if comments not open. Otherwise, if dragscroll
	// is enabled on only some pages, people would be confused?
	$on_complete = comments_open() ? "" : "debiki.Utterscroll.enable();";

	echo "
    <meta name='viewport' content='initial-scale=1.0, minimum-scale=0.01'/>
	 <link rel='stylesheet' href='" . $res . "jquery-ui/jquery-ui-1.8.16.custom.css'>
	 <link rel='stylesheet' href='" . $res . "debiki.css'>";

	# For production.
	if (true) echo "
		<script>
		  var debiki = { scriptLoad: $.Deferred() };
		  Modernizr.load({
		    test: Modernizr.touch,
		    yep: '".$res."combined-debiki-touch.min.js',
		    nope: '".$res."combined-debiki-desktop.min.js',
		    both: '".$theme_specific.debiki_theme_specific_javascript_file_name()."',
		    complete: function() {".
				$on_complete."
			}
		});
		</script>";

	# For development.
	if (false) echo "
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
			'" . $res . "debiki.js',
			'" . $theme_specific.debiki_theme_specific_javascript_file_name()."'],
			complete: function() {".
				$on_complete."
			}
		});
		</script>";

	echo "
		<link rel='stylesheet' href='".
				$theme_specific.debiki_theme_specific_style_file_name()."'>";
}



#===========================================================
# Form submission
#===========================================================


add_action('template_redirect', '\Debiki\handle_any_form_subbmission');

function handle_any_form_subbmission(){
	if (isset($_POST['debiki-action-nonce'])) {
		require_once 'debiki-handle-form-submission.php';
		handle_form_subbmission();

		## In the future, perhaps redirect to page specified in hidden input,
		## (use get_permalink() when rendering <form>  ?)

		die();
	}
}

