<?php

/**
 * Copyright (c) 2012 Kaj Magnus Lindberg
 * License: GPL v2 or later
 */

# You could check out this file: wp-comments-post.php,
# it does similar things.

namespace Debiki;

require_once('debiki-database.php');

define('DEBIKI_COMMENT_RATING_COOKIE', 'debiki_comment_rating_'.COOKIEHASH);


class ForbiddenException extends \Exception {
	function __construct($message) {
		$this->message = $message;
	}
}


function handle_form_subbmission() {
	try {
		handle_form_subbmission_or_throw();
	}
	catch (ForbiddenException $exception) {
		header('HTTP/1.1 403 Forbidden');
		die($exception->message);
	}
}


function handle_form_subbmission_or_throw() {

	if ($_SERVER['REQUEST_METHOD'] !== 'POST')
		throw_forbidden('Bad request method');

	# `_safe` means the variable has been sanitized.
	$user_id_safe = get_current_user_id();
	$post_id_safe = get_int_throw_unless_gtz('post-id');
	$comment_id_safe = get_int_throw_unless_gtz('comment-id');

	# WordPress' comment.php filters the IP in this way.
	$ip_addr_safe =
		preg_replace('/[^0-9a-fA-F:., ]/', '', $_SERVER['REMOTE_ADDR']);

	# Convert rating to boolean
	$liked_it = get_string_or_throw('vote-value');
	if ($liked_it === '+1') $liked_it_safe = true;
	else if ($liked_it === '-1') $liked_it_safe = false;
	else throw_forbidden("Bad input: vote-value");

	# Find any name and email, for unregistered users.
	# `wp_get_current_commenter` finds the name and email from cookies,
	# which needs to be sanitized first.
	/* Should I really save user's name?
	sanitize_comment_cookies();
	$author = wp_get_current_commenter();
	$author_name_safe = $author['comment_author'];
	$author_email_safe = $author['comment_author_email'];
	*/

	$cookie_value_safe = get_comment_rating_cookie_value_safe_or_throw();

	$rating = Comment_Rating::create()
			->post_id($post_id_safe)
			->comment_id($comment_id_safe)
			->actor_user_id($user_id_safe)
			->actor_cookie($cookie_value_safe)
			->actor_ip($ip_addr_safe)
			->liked_it($liked_it_safe);
			#->by_name_email($author_name_safe, $author_email_safe)

	insert_or_update_rating($rating);
}


function insert_or_update_rating($new_rating) {
	$debiki_db = new Debiki_Database();
	$comment_ratings = $debiki_db->load_comment_ratings_for_comment(
			$new_rating->comment_id());
	$earlier_version = $comment_ratings->find_earlier_version_of($new_rating);

	if ($earlier_version->found_with_same_uid_or_cookie()) {
		$debiki_db->update_comment_action($earlier_version, $new_rating);
	}
	else if ($earlier_version->num_ratings_same_ip() >=
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
		throw_forbidden('Too many comment rating attempts from this IP');
	}
	else {
		$new_cookie_value = random_string(12);
		set_comment_rating_cookie($new_cookie_value);
		$new_rating->actor_cookie($new_cookie_value);
		$debiki_db->insert_comment_action($new_rating);
	}
}


function set_comment_rating_cookie($value) {
	$lifetime = 30000000;
	setcookie(DEBIKI_COMMENT_RATING_COOKIE, $value,
			time() + $lifetime, COOKIEPATH, COOKIE_DOMAIN);
}


function get_comment_rating_cookie_value_safe_or_throw() {
	$value = $_COOKIE[DEBIKI_COMMENT_RATING_COOKIE];
	$matches_evil_char = preg_match('/[^0-9a-zA-Z]/', $value);
	if ($matches_evil_char) throw_forbidden('Bad comment rating cookie');
	return $value ? $value : '';
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


function throw_forbidden($error_message) {
	throw new ForbiddenException($error_message);
}


function get_string_or_throw($input_name) {
	if (!isset($_POST[$input_name])) {
		throw_forbidden("Missing input: $input_name");
	}
	return $_POST[$input_name];
}


function get_int_or_throw($input_name) {
	$value_str = get_string_or_throw($input_name);
	$value_int = (int) $value_str;
	return $value_int;
}


function get_int_throw_unless_gtz($input_name) {
	$value = get_int_or_throw($input_name);
	if ($value <= 0) {
		throw_forbidden("Bad input: $input_name");
	}
	return $value;
}
