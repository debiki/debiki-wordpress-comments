<?php

add_filter('debiki_comment_section_classes', function($classes) {
	return $classes.' entry-content';
});

include_once('default-v0-comments.php');
