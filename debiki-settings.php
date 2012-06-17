<?php


function debiki_echo_settings_page() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}

	echo 'Settings page, not finished.';

	# Example page:
	# http://codex.wordpress.org/Adding_Administration_Menus#Sample_Menu_Page

	# See:
	# http://codex.wordpress.org/Settings_API, and
	# http://codex.wordpress.org/Creating_Options_Pages
	# But it's somewhat deprecated -- and instead links to:
	# http://ottopress.com/2009/wordpress-settings-api-tutorial/
	#
}


?>