<?php

/**
 * Copyright (c) 2012 Kaj Magnus Lindberg
 * License: GPL v2 or later
 */

# You could check out this file: wp-comments-post.php,
# it does similar things.

namespace Debiki;

require_once('debiki-database.php');


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

	create_or_update_rating($rating);
}


function create_or_update_rating($new_rating) {
	$debiki_db = new Debiki_Database();
	$comment_ratings =
			$debiki_db->load_comment_ratings_for_comment($new_rating->comment_id);
	$earlier_version = $comment_ratings->find_earlier_version_of($new_rating);

	if ($earlier_version->found()) {
		$debiki_db->update_comment_rating($earlier_version, $new_rating);
	}
	else if ($earlier_version->num_others_same_ip >=
			Debiki_Database::max_rating_rows_per_comment_and_ip) {
		# Create/update a catch all rating for this IP, so a single IP
		# cannot insert terribly many rows into the database.
		#
		# (This is not vote fraud detection — that's handled elsewhere — this
		# is only intended to stop a certain DoS attack: unless a WordPress
		# blog restricts # comments from a single IP, and # ratings,
		# it'd be possible to consume all disk space, by uploading comments
		# and ratings? This plugin is about raings so I'm trying to
		# restrict # ratings. Restricting # comments should be done
		# by some other module, perhaps a comment spam filter?)

		# Could:
		# $debiki_db->update_catch_all_by_ip_rating($new_rating);
		# But for now:
		die_forbidden('Too many comment rating attempts from this IP');
	}
	else {
		$new_cookie_value = random_string(10);
		set_comment_rating_cookie($new_cookie_value);
		$new_rating->actor_cookie = $new_cookie_value;
		$debiki_db->create_comment_action($new_rating);
	}
}


function set_comment_rating_cookie($value) {
	$lifetime = 30000000;
	setcookie('debiki_comment_rating_'.COOKIEHASH, $value,
			time() + $lifetime, COOKIEPATH, COOKIE_DOMAIN);
	$new_cookie = $_COOKIE['debiki_comment_rating_'.COOKIEHASH];
	return $new_cookie;
}


function random_string($length, $valid_chars =
			'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789') {
	$string = '';
	$num_valid_chars = strlen($valid_chars);
	while ($length--) {
		$string .= $valid_chars[mt_rand(0, $num_valid_chars - 1)];
	}
	return $string;
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
