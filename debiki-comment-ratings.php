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

	var $action_id = 0;

	# Right now only comments, 'C', supported.
	var $action_type = 'C';

	# Must be either +1 or -1 right now.
	var $action_value_byte = 0;

	# In the future: The first rating tag. Additional tags concatenated and
	# stored in $action_value_text.
	var $action_value_tag = '';

	# In the future: Would store comment thread summary, or additional
	# rating tags (beyond the first one).
	var $action_value_text = '';

	# In the future: An array with any rating tags. (You'd
	# tag your ratings with 'interesting', 'funny', 'faulty', etcetera.)
	var $action_value_tags = null;

	var $creation_date_utc = null;
	var $post_id = 0;
	var $comment_id = 0;
	var $actor_name = '';
	var $actor_email = '';
	var $actor_ip = '';
	var $actor_cookie = '';
	var $actor_user_id = 0;

}


/**
 * Identifies an earlier version of an action A. When A is to be
 * saved, and there is an earlier version of A, then, no new row is inserted,
 * instead the earlier version is updated/overwritten.
 * This makes it possible for a user to change his/her earlier rating
 * of a certain comment (and makes it harder him/her to pretend to be
 * many different persons).
 *
 * Everything is for one single comment only. So e.g.:
 * same_by_user_id[0]->comment_id must == $action->comment_id.
 */
class Earlier_Action {

	# Location methods and results. At most one earlier version should be found,
	# per method — that is, the arrays should contain at most one elem.
	# The methods are listed in order of precedence — that is,
	# if $same_by_1_user_id is non empty, and $same_by_4_ip is non empty,
	# then the earlier action is  $same_by_1_user_id[0]  and  $same_by_4_ip[0]
	# should be ignored.
	var $same_by_user_id = array();
	var $same_by_cookie = array();

	/**
	 * This action should have no user id or email or cookie specified.
	 */
	var $same_by_ip = array();

	/**
	 * When saving a new rating:
	 * If there are very many ratings from one single ip, then, if
	 * the new rating doesn't match any of those other ratings,
	 * a generic action will be created for this IP. It is stored
	 * in  $same_by_ip  (it is not recreated if it already exists).
	 * Then the new rating replaces that catch-all $same_by_ip rating,
	 * so no new rating is created. This stops people from inserting
	 * excessively many actions into the database (a DoS attack).
	 * (Vote fraud is handled elsewhere, namely when rendering a page
	 * and sorting comments.)
	 */
	var $num_others_same_ip = 0;

	/**
	 * The action whose earlier version is located by this locator.
	 */
	var $action;

	private function __construct($action) {
		$this->action = $action;
	}

	public static function for_($action) {
		return new Earlier_Action($action);
	}

	public function found() {
		return 0 <
			count($this->same_by_user_id) +
			count($this->same_by_cookie) +
			count($this->same_by_ip);
	}
}


/**
 * Programatically constructs what corresponds to a db row in
 * wp_dw0_comment_actions and perhaps some rows in
 * wp_dw0_comment_action_tags.
 */
class Debiki_Comment_Rating extends Debiki_Action {

	private function __construct() {
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
		$this->actor_ip = $ip;
		return $this;
	}

	function by_user_id($user_id) {
		assert(is_int($user_id));
		$this->actor_user_id = $user_id;
		return $this;
	}

	function by_name_email($name, $email) {
		assert(is_string($name));
		assert(is_string($email));
		$this->actor_name = $name;
		$this->actor_email = $email;
		return $this;
	}

	function set_actor_cookie($value) {
		assert(is_string($value));
		$this->actor_cookie = $value;
		return $this;
	}

	function liked_it($liked_it) {
		assert(is_bool($liked_it));
		$this->action_value_byte = $liked_it ? 1 : -1;
		return $this;
	}

	/**
	 * Is static, so works also with a rating loaded from database.
	 * What? No it doesn't! We load an `array(...)` from the db :-(
	 */
	static function is_valid($rating) {
		$ok = $rating->action_type === 'C';  # for now
		$ok = $rating->action_value_byte === 1 ||  # for now
				$rating->action_value_byte === -1;
		$ok &= is_int($rating->post_id);
		$ok &= is_int($rating->comment_id);
		$ok &= is_int($rating->actor_user_id);
		$ok &= is_string($rating->actor_name);
		$ok &= is_string($rating->actor_email);
		$ok &= is_string($rating->actor_ip);
		$ok &= is_string($rating->actor_cookie);
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


	function find_earlier_version_of($rating) {
		$ratings = ratings_for_comment($rating->comment_id);

		function is_same($a, $b) {
			return $a && $b && $a == $b;
		}

		function undef_or_same($a, $b) {
			return !$a || !$b || $a == $b;
		}

		/*function not_different_ratings($a, $b) {
			return
				undef_or_same($a->actor_user_id, $b->actor_user_id) &&
				undef_or_same($a->actor_cookie, $b->actor_cookie) &&
				undef_or_same($a->actor_ip, $b->actor_ip);
		}*/

		# Create a mostly unitialized earlier version.
		$earlier_version = Earlier_Action::for_($rating);

		# Find any earlier version, and update its member fields.
		# (If there are many earlier versions (there should not be),
		# we find them all — this simplifies debugging/testing I think.)
		foreach ($ratings as $same_perhaps) {
			if (is_same($same_perhaps->actor_user_id, $rating->actor_user_id)) {
				$earlier_version->same_by_user_id[] = $same_perhaps;
			}
			if (is_same($same_perhaps->actor_cookie, $rating->actor_cookie)) {
				$earlier_version->same_by_cookie[] = $same_perhaps;
			}
			if (is_same($same_perhaps->actor_ip, $rating->actor_ip)) {

				$not_different =
						undef_or_same(
							$same_perhaps->actor_user_id, $rating->actor_user_id) &&
						undef_or_same(
							$same_perhaps->actor_cookie, $rating->actor_cookie);

				if ($not_different) {
					$earlier_version->same_by_ip[] = $same_perhaps;
				}
				else {
					$earlier_version->num_others_same_ip += 1;
				}
			}
		}
	}


	function ratings_for_comment($comment_id) {
		if (!array_key_exists($comment_id, $this->ratings_by_comment_id))
			return array();

		$ratings = $this->ratings_by_comment_id[$comment_id];
		return $ratings;
	}


	/**
	 * Comments with high sort scores should be placed before comments with
	 * low scores.
	 * The sort score is a float (not an int).
	 *
	 * Thwarts some kinds of vote fraud: restricts number of ratings
	 * per user id / cookie / IP.
	 *
	 * In the future:
	 * Exactly how the sort score is calculated depends on how this
	 * Debiki_Comment_Ratings instance has been configured.
	 */
	function sort_score_for_comment($comment_id) {
		$sort_score = 0.0;
		$ratings = $this->ratings_for_comment($comment_id);

		$user_ids_voted = array();
		$cookies_voted = array();
		$ip_vote_count = array();

		# In the future: The algorithm used here should depend on the
		# algorithm specified in a call to configure_sort_score_algorithm.
		foreach ($ratings as $rating) {

			# Each registered user may vote at most once.
			if ($rating->actor_user_id != 0) {
				if (isset($user_ids_voted[$rating->actor_user_id]))
					continue;
				$user_ids_voted[$rating->actor_user_id] = true;
			}

			# Each cookie may vote at most once.
			if (!empty($rating->actor_cookie)) {
				if (isset($cookies_voted[$rating->actor_cookie]))
					continue;
				$cookies_voted[$rating->actor_cookie] = true;
			}

			# Each IP may vote only a certain number of times.
			if (!empty($rating->actor_ip)) {
				if (!isset($ip_vote_count[$rating->actor_ip])) {
					$ip_vote_count[$rating->actor_ip] = 0;
				} else {
					$count = & $ip_vote_count[$rating->actor_ip];
					if ($count >= 10) // for now, at most 10 votes per IP
						continue;
					++$count;
					assert($ip_vote_count[$rating->actor_ip] >= 1);
				}
			}

			$sort_score += $rating->action_value_byte;
		}

		# COULD cache this value in a database cache table, and refresh
		# only when someone votes.
		return $sort_score;
	}


	/**
	 * Returns an int if +1 / -1 rating system is used, oterwise perhaps
	 * a float.
	 *
	 * If a user has tagged the coment with both positive and negative rating
	 * tags, this would increment both num_upvotes and num_downvotes,
	 * with values < 1.0.
	 */
	function upvote_count_for_comment($comment_id) {
		return $this->_count_ratings($comment_id, 1);
	}


	function downvote_count_for_comment($comment_id) {
		return $this->_count_ratings($comment_id, -1);
	}


	private function _count_ratings($comment_id, $value) {
		# $comment_id is a string when invoked from the comment rendering loop.
		$comment_id = (int) $comment_id;
		$ratings = $this->ratings_for_comment($comment_id);
		$count = 0;
		foreach ($ratings as $rating) {
			# In the future, what happens here would depend on
			# the sort_score_algorithm().
			if ($rating->action_value_byte == $value) {
				++$count;
			}
		}
		return $count;
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

	/**
	 * How comment ratings are converted to sortable values.
	 */
	# function sort_score_algorithm();

}
