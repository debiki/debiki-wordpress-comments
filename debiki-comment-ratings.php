<?php
/**
 * Copyright (c) 2012 Kaj Magnus Lindberg
 * License: GPL v2 or later
 */

namespace Debiki;


/**
 * Corresponds to a db row in wp_dw0_comment_actions.
 *
 * Can be created from a database row (an array),
 * or programatically via setters.
 */
class Action {

	protected $data;

	protected function __construct(& $database_row = null) {
		if ($database_row) {
			assert(is_object($database_row));
			$this->data = & $database_row;
			return;
		}

		$this->data = new \stdClass;
		$this->action_id(0);
		$this->action_type('C');
		$this->data->action_value_byte = 0;
		$this->data->action_value_tag = null;  # not yet any accessors
		$this->data->action_value_text = null; #
		# The date must be inited here, or unit test diffs fails —
		# it seems PHPUnit thinks that members must be declared in the
		# correct order (which matches the order in which they're read from db).
		$this->data->creation_date_utc = null;
		$this->data->modification_date_utc = null;
		$this->data->modification_count = 0;
		$this->post_id(0);
		$this->comment_id(0);
		$this->data->actor_name = null;  # not yet any accessors
		$this->data->actor_email = null; #
		$this->actor_ip('');
		$this->actor_cookie('');
		$this->actor_user_id(0);
	}

	function action_id($value = null) {
		assert($value === null || is_int($value));
		return $this->_get_or_set('action_id', $value);
	}

	/** Right now only comments, 'C', supported. */
	function action_type($value = null) {
		assert($value === null || $value === 'C');
		return $this->_get_or_set('action_type', $value);
	}

	/** Set by subclasses. */
	function action_value_byte() {
		$d = $this->data;
		$b = $d->action_value_byte;
		return $this->data->action_value_byte;
	}

	/**
	 * In the future: The first rating tag. Additional tags concatenated and
	 * stored in $action_value_text.
	 *   What? Then why have I also added function action_value_tags ??
	 */
	#function action_value_tag($value = null) {
	#	assert($value === null || is_string($value));
	#	return $this->_get_or_set('action_value_tag', $value);
	#}

	/**
	 * In the future: Would store comment thread summary, or additional
	 * rating tags (beyond the first one).
	 */
	#function action_value_text($value = null) {
	#	assert($value === null || is_string($value));
	#	return $this->_get_or_set('action_value_text', $value);
	#}

	/**
	 * In the future: An array with any rating tags. (You'd
	 * tag your ratings with 'interesting', 'funny', 'faulty', etcetera.)
	 */
	#function action_value_tags($value = null) {
	#	assert($value === null); # cannot set, right now
	#	return $this->_get_or_set('action_value_tags', $value);
	#}

	function creation_date_utc($value = null) {
		# A date read from db is a string, so use strings, for now, hmm.
		assert($value === null || is_string($value));
		return $this->_get_or_set('creation_date_utc', $value);
	}

	function post_id($value = null) {
		assert($value === null || is_int($value));
		return $this->_get_or_set('post_id', $value);
	}

	function comment_id($value = null) {
		assert($value === null || is_int($value));
		return $this->_get_or_set('comment_id', $value);
	}

	function actor_ip($value = null) {
		assert($value === null || is_string($value));
		return $this->_get_or_set('actor_ip', $value);
	}

	function actor_cookie($value = null) {
		assert($value === null || is_string($value));
		return $this->_get_or_set('actor_cookie', $value);
	}

	function actor_user_id($value = null) {
		assert($value === null || is_int($value));
		return $this->_get_or_set('actor_user_id', $value);
	}

	protected function _get_or_set($member, $value) {
		if ($value === null)
			return $this->data->$member;

		$this->data->$member = $value;
		return $this;
	}

}


class Comment_Rating extends Action {

	protected function __construct($database_row = null) {
		parent::__construct($database_row);
	}

	static function create() {
		return new Comment_Rating;
	}

	static function from_db_row($database_row) {
		return new Comment_Rating($database_row);
	}

	function liked_it($liked_it) {
		assert(is_bool($liked_it));
		$value = $liked_it ? 1 : -1;
		$this->data->action_value_byte = $value;
		assert($this->action_value_byte() === $value);
		return $this;
	}

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
	 * OLD COMMENT When saving a new rating:
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
	function num_ratings_same_ip() {
		return count($this->same_by_ip);
	}

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

	public function found_with_same_uid_or_cookie() {
		return 0 <
			count($this->same_by_user_id) +
			count($this->same_by_cookie);
	}
}


/**
 * Ratings for comments on one single page/post.
 */
class Comment_Ratings {

	private $actions = array();
	private $ratings_by_comment_id = array();
	private $post_id = -1;


	private function __construct(& $ratings) {
		$this->actions = & $ratings;
		foreach ($this->actions as & $action) {
			if ($this->post_id === -1) $this->post_id = $action->post_id();
			else assert('$this->post_id == $action->post_id() /*same page*/');

			if ('C' == $action->action_type())
				$this->ratings_by_comment_id[$action->comment_id()][] = & $action;
		}
	}

	/**
	 * All ratings must be for comments on the same page.
	 */
	static function with(& $ratings) {
		return new Comment_Ratings(& $ratings);
	}

	/**
	 * All ratings must be for comments on the same page.
	 */
	static function from_db_rows(& $rating_rows) {
		$ratings = array();
		foreach ($rating_rows as & $rating_row) {
			$ratings[] = Comment_Rating::from_db_row(& $rating_row);
		}
		return Comment_Ratings::with(& $ratings);
	}


	function find_earlier_version_of($rating) {
		$ratings = $this->ratings_for_comment($rating->comment_id());

		# Create a mostly unitialized earlier version.
		$earlier_version = Earlier_Action::for_($rating);

		# Find any earlier version, and update its member fields.
		# (If there are many earlier versions (there should not be),
		# we find them all — this simplifies debugging/testing I think.)
		foreach ($ratings as $same_maybe) {

			if ($this->_is_same(
					$same_maybe->actor_user_id(), $rating->actor_user_id())) {
				$earlier_version->same_by_user_id[] = $same_maybe;
			}

			if ($this->_is_same(
					$same_maybe->actor_cookie(), $rating->actor_cookie())) {
				$earlier_version->same_by_cookie[] = $same_maybe;
			}

			if ($this->_is_same($same_maybe->actor_ip(), $rating->actor_ip())) {
				$earlier_version->same_by_ip[] = $same_maybe;
			}
		}

		return $earlier_version;
	}

	private function _is_same($a, $b) {
		return $a && $b && $a == $b;
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
			if ($rating->actor_user_id() != 0) {
				if (isset($user_ids_voted[$rating->actor_user_id()]))
					continue;
				$user_ids_voted[$rating->actor_user_id()] = true;
			}

			# Each cookie may vote at most once.
			if (strlen($rating->actor_cookie())) {
				if (isset($cookies_voted[$rating->actor_cookie()]))
					continue;
				$cookies_voted[$rating->actor_cookie()] = true;
			}

			# Each IP may vote only a certain number of times.
			if (strlen($rating->actor_ip())) {
				if (!isset($ip_vote_count[$rating->actor_ip()])) {
					$ip_vote_count[$rating->actor_ip()] = 0;
				} else {
					$count = & $ip_vote_count[$rating->actor_ip()];
					if ($count >= 10) // for now, at most 10 votes per IP
						continue;
					++$count;
					assert($ip_vote_count[$rating->actor_ip()] >= 1);
				}
			}

			$sort_score += $rating->action_value_byte();
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
			if ($rating->action_value_byte() == $value) {
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
