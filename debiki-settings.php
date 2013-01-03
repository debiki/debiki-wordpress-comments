<?php
/**
 * Copyright (c) 2012 Kaj Magnus Lindberg (born 1979)
 * License: GPL v2 or later
 */


add_action('admin_init', 'debiki_admin_init');

function debiki_admin_init() {
	register_setting(
		'debiki_main_settings_group',  # settings group name
		'debiki_settings',  # option db entry name
		'debiki_validate_main_settings_option');

	add_settings_section(
		'debiki_settings_section_attribution',  # section slug-name
		'Attribution',  # title
		'debiki_main_settings_section_text', # function that echos section intro text
		'debiki_settings_page');  # settings page slug-name

	add_settings_field(
		'debiki_show_attribution_link',  # setting slug-name, also used as html id attr.
		'Show attribution link',  # title
			# could: "... , if any comments shown" and show only if >= 2 comments?
		'debiki_setting_string',  # function that fills the field with form inputs
		'debiki_settings_page',  # settings page slug-name
		'debiki_settings_section_attribution');  # section slug-name
}


function debiki_main_settings_section_text() {
	?>
	<p>
		If you want to, you can show a "Comments powered by Debiki WordPress Comments"
		link, to Debiki WordPress Comments homepage, below the reply form.<br>
		Alternatively,
		please consider adding a link from an <em>About-Your-Website</em> page
		or a <em>Credits</em> page, to <kbd>http://wordpress.debiki.com</kbd>
	</p>
	<?php
}


function debiki_setting_string() {
	$options = get_option('debiki_settings');
	$setting_attr_link = $options['debiki_setting_attribution_link'];

	$checked_html_attr = '';
	if ($setting_attr_link == 'show')
		$checked_html_attr = ' checked="checked"';

	echo "<input type='checkbox' $checked_html_attr value='show'
			name='debiki_settings[debiki_setting_attribution_link]' />";
}


function debiki_validate_main_settings_option($input_unsafe) {
	$setting_attr_link = $input_unsafe['debiki_setting_attribution_link'];
	if ($setting_attr_link != 'show')
		$setting_attr_link = 'hide';

	$input_safe = array();
	$input_safe['debiki_setting_attribution_link'] = $setting_attr_link;

	return $input_safe;
}


function debiki_echo_settings_page() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}

	?>
	<h1>Debiki WordPress Comments Settings</h1>

	<br>

	<form action="options.php" method="post">
		<?php settings_fields('debiki_main_settings_group'); ?>
		<?php do_settings_sections('debiki_settings_page'); ?>

		<br><br>
		<input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
	</form>

	<!-- p>
		<a href = "http://wordpress.debiki.com">Debiki Wordpress Comments</a>
		is licensed under the
		<a rel = "license" href="http://www.gnu.org/licenses/gpl-2.0.html">
			GNU General Public License, version 2 or later</a>.
	</p -->
	<?php

	# Example page:
	# http://codex.wordpress.org/Adding_Administration_Menus#Sample_Menu_Page

	# See:
	# http://codex.wordpress.org/Settings_API, and
	# http://codex.wordpress.org/Creating_Options_Pages
	# But it's somewhat deprecated -- and instead links to:
	# http://ottopress.com/2009/wordpress-settings-api-tutorial/
	#

	# And: ?
	# http://alisothegeek.com/2011/01/wordpress-settings-api-tutorial-1/
}

