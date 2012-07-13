<?php
/**
 * Copyright (c) 2012 Kaj Magnus Lindberg
 * License: GPL v2 or later
 */

namespace Debiki;


/**
 * Corresponds to a db row in wp_dw0_comment_actions.
 */
class Debiki_Action {

	var $action_id;

	# Right now only comments, 'C', supported.
	var $action_type;

	# Must be either +1 or -1 right now.
	var $action_value_byte;

	# In the future: Would store comment thread summary.
	var $action_value_text;

	# In the future: An array with any rating tags. (You'd
	# tag your ratings with 'interesting', 'funny', 'faulty', etcetera.)
	var $action_value_tags;

	var $creation_date_utc;
	var $post_id;
	var $comment_id;
	var $actor_name;
	var $actor_email;
	var $actor_ip;
	var $actor_user_id;

}


/**
 * Programatically constructs what corresponds to a db row in
 * wp_dw0_comment_actions and perhaps some rows in
 * wp_dw0_comment_action_tags.
 */
class Debiki_Comment_Rating extends Debiki_Action {

	private function __construct() {
		$this->action_type = 'C';
		$this->action_value_tags = array();
	}

	static function for_post_comment($post_id, $comment_id) {
		assert(is_int($post_id));
		assert(is_int($comment_id));
		$rating = new Debiki_Comment_Rating();
		$rating->post_id = $post_id;
		$rating->comment_id = $comment_id;
		return $rating;
	}

	function from_ip($ip) {
		assert(is_string($ip));
		$this->ip = $ip;
		return $this;
	}

	function by_user($user_id) {
		assert(is_int($user_id));
		$this->actor_user_id = $user_id;
		return $this;
	}

	function liked_it($liked_it) {
		assert(is_bool($liked_it));
		$this->action_value_byte = $liked_it ? 1 : -1;
		return $this;
	}

	/**
	 * Is static, so works also with a rating loaded from database.
	 */
	static function is_valid($rating) {
		$ok = $rating->action_type === 'C';  # for now
		$ok = $rating->action_value_byte === 1 ||
				$rating->action_value_byte === -1;
		$ok &= is_int($rating->post_id);
		$ok &= is_int($rating->comment_id);
		return $ok;
	}
}



class Debiki_Comment_Ratings {

	var $action_rows;
	var $ratings_by_comment_id = array();

	private function __construct() {}


	static function from_action_rows(& $action_rows) {
		$comment_ratings = new Debiki_Comment_Ratings();
		$comment_ratings->action_rows = & $action_rows;

		foreach ($action_rows as $action) {
			if ('C' == $action->action_type)
				$comment_ratings->ratings_by_comment_id[$action->comment_id][] =
						$action;
		}

		return $comment_ratings;
	}


	function get_ratings_for_comment($comment_id) {
		if (!array_key_exists($comment_id, $this->ratings_by_comment_id))
			return array();

		$ratings = $this->ratings_by_comment_id[$comment_id];
		return $ratings;
	}


	/**
	 * Comments with low sort scores should be placed before comments with
	 * high scores.
	 * The sort score is a float (not an int).
	 *
	 * In the future:
	 * Exactly how the sort score is calculated depends on how this
	 * Debiki_Comment_Ratings instance has been configured.
	 */
	function get_sort_score_for_comment($comment_id) {
		$sort_score = 0.0;
		$ratings = $this->get_ratings_for_comment($comment_id);

		# In the future: The algorithm used here should depend on the
		# algorithm specified in a call to configure_sort_score_algorithm.
		foreach ($ratings as $rating) {
			$sort_score += $rating->action_value_byte;
		}

		return $sort_score;
	}


	/**
	 * Specifies which algorithm to use when calculating sort scores.
	 * By default, each 'like' results in +1, and each 'disslike' in -1.
	 * Then the resulting number is negated, so comments with many ratings
	 * appear first (we're sorting in ascending order).
	 * — However! There is a problem with this approach: Comments that
	 * gets many ratings early on, and somewhat more likes than disses, gets
	 * good visibility. At the cost of *better* comments, that are posted later
	 * on.
	 * — But there is at least one other algorithm that mitigate this problem:
	 * Calculate confidence bounds on how much people like each comment, and
	 * sort by lower confidence bound (descending sort order).
	 */
	# function configure_sort_score_algorithm(...);

}
