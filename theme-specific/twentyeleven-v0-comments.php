<?php
/**
 * Copyright (c) 2012 Kaj Magnus Lindberg (born 1979)
 * License: GPL v2 or later
 */

add_filter('debiki_comment_section_classes', function($classes) {
	return $classes.' entry-content';
});

include_once('default-v0-comments.php');
