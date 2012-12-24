<?php
/**
 * Copyright (c) 2012 Kaj Magnus Lindberg (born 1979)
 * License: GPL v2 or later
 */


function debiki_echo_settings_page() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}

	?>
	<h1>Debiki WordPress Comments Settings</h1>
	<p>Not implemented.</p>
	<p>
		<a href = "http://wordpress.debiki.com">Debiki Wordpress Comments</a>
		is licensed under the
		<a rel = "license" href = "http://creativecommons.org/licenses/by-sa/3.0/deed.en_US">
			GNU General Public License, version 2 or later</a>.
	</p>
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

