<?php

/**
 * Copyright (c) 2012 Kaj Magnus Lindberg
 * License: GPL v2 or later
 */

# You could check out this file: wp-comments-post.php,
# it does similar things.


namespace Debiki;


function handle_form_subbmission() {

	if ($_SERVER['REQUEST_METHOD'] !== 'POST')
		die_forbidden('Bad request method');

	# `_safe` means the variable has been sanitized.
	$user_id_safe = get_current_user_id();
	$post_id_safe = get_int_die_unless_gtz('post-id');
	$comment_id_safe = get_int_die_unless_gtz('comment-id');

	# WordPress' comment.php filters the IP in this way.
	$ip_addr_safe =
		preg_replace('/[^0-9a-fA-F:., ]/', '', $_SERVER['REMOTE_ADDR']);

	# Convert rating to boolean
	$liked_it = get_string_or_die('vote-value');
	if ($liked_it === '+1') $liked_it_safe = true;
	else if ($liked_it === '-1') $liked_it_safe = false;
	else die_forbidden("Bad input: vote-value");

	# Find any name and email, for unregistered users.
	# `wp_get_current_commenter` finds the name and email from cookies,
	# which needs to be sanitized first.
	sanitize_comment_cookies();
	$author = wp_get_current_commenter();
	$author_name_safe = $author['comment_author'];
	$author_email_safe = $author['comment_author_email'];

	$rating = Debiki_Comment_Rating::for_post_comment(
			$post_id_safe, $comment_id_safe)
			->from_ip($ip_addr_safe)
			->by_user_id($user_id_safe)
			->by_name_email($author_name_safe, $author_email_safe)
			->liked_it($liked_it_safe);

	# Save rating
	require_once('debiki-database.php');
	$debiki_db = new Debiki_Database();
	$debiki_db->save_comment_rating($rating);
}


function die_forbidden($error_message) {
	header('HTTP/1.1 403 Forbidden');
	die($error_message);
}


function get_string_or_die($input_name) {
	if (!isset($_POST[$input_name])) {
		die_forbidden("Missing input: $input_name");
	}
	return $_POST[$input_name];
}


function get_int_or_die($input_name) {
	$value_str = get_string_or_die($input_name);
	$value_int = (int) $value_str;
	return $value_int;
}


function get_int_die_unless_gtz($input_name) {
	$value = get_int_or_die($input_name);
	if ($value <= 0) {
		die_forbidden("Bad input: $input_name");
	}
	return $value;
}
