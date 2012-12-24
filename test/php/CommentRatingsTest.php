<?php
/**
 * Copyright (c) 2012 Kaj Magnus Lindberg (born 1979)
 * License: GPL v2 or later
 */


namespace Debiki\CommentRatingsTest;

use \Debiki\Comment_Ratings as Comment_Ratings;
use \Debiki\Comment_Rating as Comment_Rating;

require_once DEBIKI_PLUGIN_DIR.'debiki-database.php';


/**
 * Tests Comment_Ratings->find_earlier_version_of($rating).
 *
 * (Currently does no tests without cookies, because create_or_update_rating()
 * always creates a cookie, when creating a rating.)
 */
class Find_Earlier_Version_Test extends \WP_UnitTestCase {

	public function setUp() {
		parent::setUp();
	}

	public function test_with_no_other_ratings_and_count_ip() {
		$rating = new_rating();
		$ratings = Comment_Ratings::none();
		$earlier_version = $ratings->find_earlier_version_of($rating);
		$this->assertFalse($earlier_version->found_with_same_uid_or_cookie());
		$this->assertEquals(0, $earlier_version->num_ratings_same_ip());
	}

	public function test_with_one_rating_for_other_comment_and_count_ip() {
		$earlier_version = $this->check_earlier_version_absent(
				new_rating(), new_rating()->comment_id(999));
		$this->assertEquals(0, $earlier_version->num_ratings_same_ip());
	}

	public function test_with_one_rating_by_completely_other_and_count_ip() {
		$earlier_version = $this->check_earlier_version_absent(
				new_rating(), new_rating()
					->actor_user_id(888)
					->actor_cookie('cookie_efgh')
					->actor_ip('5.6.7.8'));
		$this->assertEquals(0, $earlier_version->num_ratings_same_ip());
	}

	public function test_with_itself_and_count_ip() {
		$rating = new_rating();
		$earlier_version = $this->check_earlier_version_exists($rating, $rating);
		$this->assertEquals(1, $earlier_version->num_ratings_same_ip());
	}

	public function test_with_copy() {
		$this->check_earlier_version_exists(
				new_rating(), new_rating());
	}

	public function test_with_one_rating_other_userid() {
		$this->check_earlier_version_exists(new_rating(),
				new_rating()->actor_user_id(888));
	}

	public function test_with_one_rating_other_cookie() {
		$this->check_earlier_version_exists(new_rating(),
				new_rating()->actor_cookie('otr-cki'));
	}

	public function test_with_one_rating_other_ip() {
		$this->check_earlier_version_exists(new_rating(),
				new_rating()->actor_ip('5.5.5.5'));
	}

	public function test_with_one_rating_other_userid_cookie() {
		$this->check_earlier_version_absent(new_rating(),
				new_rating()->actor_user_id(888)->actor_cookie('otr-cki'));
	}

	public function test_with_one_rating_other_userid_ip() {
		$this->check_earlier_version_exists(new_rating(),
				new_rating()->actor_user_id(888)->actor_ip('5.5.5.5'));
	}

	public function test_with_one_rating_other_cookie_ip() {
		$this->check_earlier_version_exists(new_rating(),
				new_rating()->actor_cookie('otr-cki')->actor_ip('5.5.5.5'));
	}

	public function test_with_no_uid_and_one_rating_other_cookie_ip() {
		$this->check_earlier_version_absent(new_rating_no_uid(),
				new_rating_no_uid()
					->actor_cookie('otr-cki')->actor_ip('5.5.5.5'));
	}

	public function test_with_no_uid_and_one_rating_other_ip() {
		$this->check_earlier_version_exists(new_rating_no_uid(),
				new_rating_no_uid()->actor_ip('5.5.5.5'));
	}

	public function test_with_no_uid_and_one_rating_other_cookie() {
		$this->check_earlier_version_absent(new_rating_no_uid(),
				new_rating_no_uid()->actor_cookie('otr-cki'));
	}

	public function test_finds_six_same__count_ip() {
		$rating = new_rating();
		$others = array();
		# Two with same user id
		$others[] = new_rating()
				->actor_cookie('otr-cki')->actor_ip('5.5.5.5');
		$others[] = new_rating()
				->actor_cookie('otr-cki2')->actor_ip('6.6.6.6');
		# Two with same cookie
		$others[] = new_rating()
				->actor_user_id(678)->actor_ip('5.5.5.5');
		$others[] = new_rating()
				->actor_user_id(901)->actor_ip('6.6.6.6');
		# Two with same IP
		$others[] = new_rating()
				->actor_user_id(678)->actor_cookie('otr-cki');
		$others[] = new_rating()
				->actor_user_id(901)->actor_cookie('otr-cki2');
		# One by someone else, and one for another comment (should not be found).
		$others[] = new_rating_by_other_user();
		$others[] = new_rating()->comment_id(111);
		$ratings = Comment_Ratings::with($others);
		$earlier_version = $ratings->find_earlier_version_of($rating);
		$this->assertTrue($earlier_version->found_with_same_uid_or_cookie());
		$this->assertEquals(2, $earlier_version->num_ratings_same_ip());
		$this->assertEquals(2, count($earlier_version->same_by_user_id));
		$this->assertEquals(2, count($earlier_version->same_by_cookie));
		$this->assertEquals(2, count($earlier_version->same_by_ip));
	}

	public function check_earlier_version_absent(& $rating, & $other_rating) {
		$ratings = Comment_Ratings::with(& $other_rating);
		$earlier_version = $ratings->find_earlier_version_of($rating);
		$this->assertFalse($earlier_version->found_with_same_uid_or_cookie());
		return $earlier_version;
	}

	public function check_earlier_version_exists(& $rating, & $other_rating) {
		$ratings = Comment_Ratings::with(& $other_rating);
		$earlier_version = $ratings->find_earlier_version_of($rating);
		$this->assertTrue($earlier_version->found_with_same_uid_or_cookie());
		return $earlier_version;
	}

}


/**
 * Tests Comment_Ratings->sort_score_for_comment($comment_id).
 */
class Sort_Score_Test extends \WP_UnitTestCase {

	public function setUp() {
		parent::setUp();
	}

	public function test_score_0_if_no_ratings() {
		$ratings = Comment_Ratings::none();
		$this->assertEquals(0, $ratings->sort_score_for_comment(0));
		$this->assertEquals(0, $ratings->sort_score_for_comment(1));
		$this->assertEquals(0, $ratings->sort_score_for_comment(123));
	}

	public function test_score_0_if_wrong_comments_rated() {
		$ratings_array = array(new_rating()->comment_id(999),
				new_rating()->comment_id(55));
		$ratings = Comment_Ratings::with($ratings_array);
		$this->assertEquals(0, $ratings->sort_score_for_comment(0));
		$this->assertEquals(0, $ratings->sort_score_for_comment(1));
		$this->assertEquals(0, $ratings->sort_score_for_comment(123));
	}

	public function test_score_1_if_one_like() {
		$ratings = Comment_Ratings::with(new_rating());
		$this->assertEquals(1, $ratings->sort_score_for_comment(2));
	}

	public function test_score_m1_if_one_diss() {
		$ratings = Comment_Ratings::with(new_rating()->liked_it(false));
		$this->assertEquals(-1, $ratings->sort_score_for_comment(2));
	}

	public function test_score_1_if_one_duplicated_like() {
		# Add 3 identical ratings, verify sort score becomes 1 not 3.
		$ratings_array = array();
		$ratings_array[] = new_rating();
		$ratings_array[] = new_rating();
		$ratings_array[] = new_rating();
		$ratings = Comment_Ratings::with(& $ratings_array);
		$this->assertEquals(1, $ratings->sort_score_for_comment(2));
	}

	public function test_score_1_two_ratings_same_userid() {
		$a = new_rating();
		$b = new_rating_by_other_user()->actor_user_id($a->actor_user_id());
		$this->check_score_after(1, $a, $b);
	}

	public function test_score_2_two_ratings_no_userid() {
		# User ID 0 means the user id is unknown — so $a and $b should be
		# considered different ratings, and both ratings should count.
		$a = new_rating_no_uid(0);
		$b = new_rating_by_other_user()->actor_user_id(0);
		$this->check_score_after(2, $a, $b);
	}

	public function test_score_1_two_ratings_same_cookie() {
		$a = new_rating();
		$b = new_rating_by_other_user()->actor_cookie($a->actor_cookie());
		$this->check_score_after(1, $a, $b);
	}

	public function test_score_2_two_ratings_no_cookie() {
		# Cookie value '' should mean the cookie is unknown?
		$a = new_rating()->actor_cookie('');
		$b = new_rating_by_other_user()->actor_cookie('');
		$this->check_score_after(2, $a, $b);
	}

	public function test_score_2_two_ratings_same_ip() {
		# Both ratings considered, since different user ids and cookies.
		$a = new_rating();
		$b = new_rating_by_other_user()->actor_ip($a->actor_ip());
		$this->check_score_after(2, $a, $b);
	}

	public function test_score_capped_very_many_ratings_same_ip() {
		$max_ratings_per_ip =
				Comment_Ratings::none()->settings['max_ratings_per_ip'];
		$ratings_array = array();
		for ($i = 1; $i <= $max_ratings_per_ip + 10; ++$i) {
			$ratings_array[] = new_rating_no_uid()->actor_cookie("cookie_no_$i");
		}
		assert(count($ratings_array) > $max_ratings_per_ip);
		$ratings = Comment_Ratings::with(& $ratings_array);
		$this->assertEquals($max_ratings_per_ip,
				$ratings->sort_score_for_comment(2));
	}

	function check_score_after($score, $rating_a, $rating_b) {
		$ratings_array = array($rating_a, $rating_b);
		$ratings = Comment_Ratings::with(& $ratings_array);
		$this->assertEquals($score, $ratings->sort_score_for_comment(2));
	}

}


/**
 * Tests Comment_Ratings->up/downvote_count_for_comment($comment_id).
 */
class Vote_Count_Test extends \WP_UnitTestCase {

	public function setUp() {
		parent::setUp();
	}

	public function test_counts_0_if_no_ratings() {
		$ratings = Comment_Ratings::none();
		$this->_check_counts($ratings, false, 0, 0, 0, 0);
	}

	public function test_counts_0_if_only_other_comments_rated() {
		$ratings = Comment_Ratings::with(new_rating()->comment_id(999));
		$this->_check_counts($ratings, false, 0, 0, 0, 0);
	}

	public function test_counts_1_with_one_like() {
		$ratings = Comment_Ratings::with(new_rating());
		$this->_check_counts($ratings, false, 1, 0, 0, 0);
	}

	public function test_counts_m1_with_one_diss() {
		$ratings = Comment_Ratings::with(new_rating()->liked_it(false));
		$this->_check_counts($ratings, false, 0, 1, 0, 0);
	}

	public function test_counts_1_with_users_like() {
		$ratings = Comment_Ratings::with(new_rating());
		$this->_check_counts($ratings, default_user_id(), 1, 0, 1, 0);
	}

	public function test_counts_m1_with_users_diss() {
		$ratings = Comment_Ratings::with(new_rating()->liked_it(false));
		$this->_check_counts($ratings, default_user_id(), 0, 1, 0, 1);
	}

	public function test_counts_1_0_with_other_users_like() {
		$ratings = Comment_Ratings::with(new_rating());
		$this->_check_counts($ratings, 999, 1, 0, 0, 0);
	}

	public function test_counts_m1_0_with_other_users_diss() {
		$ratings = Comment_Ratings::with(new_rating()->liked_it(false));
		$this->_check_counts($ratings, 999, 0, 1, 0, 0);
	}

	private function _check_counts($ratings, $user_id,
			$ups, $downs, $user_ups, $user_downs) {
		$counts = $ratings->count_ratings_for_comment(
				default_comment_id(), $user_id);
		$this->assertEquals($ups, $counts->upvote_count);
		$this->assertEquals($downs, $counts->downvote_count);
		$this->assertEquals($user_ups, $counts->users_upvote_count);
		$this->assertEquals($user_downs, $counts->users_downvote_count);
	}
}

function default_comment_id() { return 2; }
function default_user_id() { return 3; }

function new_rating() {
	return Comment_Rating::create()->post_id(1)->comment_id(2)
			->actor_user_id(3)->actor_cookie('cookie_333')
			->actor_ip('3.3.3.3')->liked_it(true);
}

function new_rating_no_uid() {
	return new_rating()->actor_user_id(0);
}

function new_rating_by_other_user() {
	return new_rating()->actor_user_id(4)->actor_cookie('cookie_444')
			->actor_ip('4.4.4.4');
}
