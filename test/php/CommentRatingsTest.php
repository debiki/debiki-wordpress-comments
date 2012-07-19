<?php

namespace Debiki;

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
		$rating = $this->new_rating();
		$ratings_array = array();
		$ratings = Comment_Ratings::with(& $ratings_array);
		$earlier_version = $ratings->find_earlier_version_of($rating);
		$this->assertFalse($earlier_version->found_with_same_uid_or_cookie());
		$this->assertEquals(0, $earlier_version->num_ratings_same_ip());
	}

	public function test_with_one_rating_for_other_comment_and_count_ip() {
		$earlier_version = $this->check_earlier_version_absent(
				$this->new_rating(), $this->new_rating()->comment_id(999));
		$this->assertEquals(0, $earlier_version->num_ratings_same_ip());
	}

	public function test_with_one_rating_by_completely_other_and_count_ip() {
		$earlier_version = $this->check_earlier_version_absent(
				$this->new_rating(), $this->new_rating()
					->actor_user_id(888)
					->actor_cookie('cookie_efgh')
					->actor_ip('5.6.7.8'));
		$this->assertEquals(0, $earlier_version->num_ratings_same_ip());
	}

	public function test_with_itself_and_count_ip() {
		$rating = $this->new_rating();
		$earlier_version = $this->check_earlier_version_exists($rating, $rating);
		$this->assertEquals(1, $earlier_version->num_ratings_same_ip());
	}

	public function test_with_copy() {
		$this->check_earlier_version_exists(
				$this->new_rating(), $this->new_rating());
	}

	public function test_with_one_rating_other_userid() {
		$this->check_earlier_version_exists($this->new_rating(),
				$this->new_rating()->actor_user_id(888));
	}

	public function test_with_one_rating_other_cookie() {
		$this->check_earlier_version_exists($this->new_rating(),
				$this->new_rating()->actor_cookie('otr-cki'));
	}

	public function test_with_one_rating_other_ip() {
		$this->check_earlier_version_exists($this->new_rating(),
				$this->new_rating()->actor_ip('5.5.5.5'));
	}

	public function test_with_one_rating_other_userid_cookie() {
		$this->check_earlier_version_absent($this->new_rating(),
				$this->new_rating()->actor_user_id(888)->actor_cookie('otr-cki'));
	}

	public function test_with_one_rating_other_userid_ip() {
		$this->check_earlier_version_exists($this->new_rating(),
				$this->new_rating()->actor_user_id(888)->actor_ip('5.5.5.5'));
	}

	public function test_with_one_rating_other_cookie_ip() {
		$this->check_earlier_version_exists($this->new_rating(),
				$this->new_rating()->actor_cookie('otr-cki')->actor_ip('5.5.5.5'));
	}

	public function test_with_no_uid_and_one_rating_other_cookie_ip() {
		$this->check_earlier_version_absent($this->new_rating_no_uid(),
				$this->new_rating_no_uid()
					->actor_cookie('otr-cki')->actor_ip('5.5.5.5'));
	}

	public function test_with_no_uid_and_one_rating_other_ip() {
		$this->check_earlier_version_exists($this->new_rating_no_uid(),
				$this->new_rating_no_uid()->actor_ip('5.5.5.5'));
	}

	public function test_with_no_uid_and_one_rating_other_cookie() {
		$this->check_earlier_version_absent($this->new_rating_no_uid(),
				$this->new_rating_no_uid()->actor_cookie('otr-cki'));
	}

	public function test_finds_six_same__count_ip() {
		$rating = $this->new_rating();
		$others = array();
		# Two with same user id
		$others[] = $this->new_rating()
				->actor_cookie('otr-cki')->actor_ip('5.5.5.5');
		$others[] = $this->new_rating()
				->actor_cookie('otr-cki2')->actor_ip('6.6.6.6');
		# Two with same cookie
		$others[] = $this->new_rating()
				->actor_user_id(678)->actor_ip('5.5.5.5');
		$others[] = $this->new_rating()
				->actor_user_id(901)->actor_ip('6.6.6.6');
		# Two with same IP
		$others[] = $this->new_rating()
				->actor_user_id(678)->actor_cookie('otr-cki');
		$others[] = $this->new_rating()
				->actor_user_id(901)->actor_cookie('otr-cki2');
		# One by someone else, and one for another comment (should not be found).
		$others[] = $this->new_rating_by_other_everything();
		$others[] = $this->new_rating()->comment_id(111);
		$ratings = Comment_Ratings::with($others);
		$earlier_version = $ratings->find_earlier_version_of($rating);
		$this->assertTrue($earlier_version->found_with_same_uid_or_cookie());
		$this->assertEquals(2, $earlier_version->num_ratings_same_ip());
		$this->assertEquals(2, count($earlier_version->same_by_user_id));
		$this->assertEquals(2, count($earlier_version->same_by_cookie));
		$this->assertEquals(2, count($earlier_version->same_by_ip));
	}

	function new_rating() {
		return Comment_Rating::create()->post_id(1)->comment_id(2)
				->actor_user_id(3)->actor_cookie('cookie_abcd')
				->actor_ip('1.2.3.4.')->liked_it(true);
	}

	function new_rating_no_uid() {
		return $this->new_rating()->actor_user_id(0);
	}

	function new_rating_by_other_everything() {
		return $this->new_rating()->actor_user_id(999)->actor_cookie('otr-cki')
				->actor_ip('5.5.5.5.');
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
