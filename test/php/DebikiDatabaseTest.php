<?php
/**
 * Copyright (c) 2012 Kaj Magnus Lindberg (born 1979)
 * License: GPL v2 or later
 */


namespace Debiki;

require_once DEBIKI_PLUGIN_DIR.'debiki-database.php';


class Debiki_Database_Test extends \WP_UnitTestCase {

	public function setUp() {
		parent::setUp();
	}


	public function test_install_for_current_blog() {
		global $wpdb;
		$db = new Debiki_Database();
		$db->install_for_current_blog();
		$this->assert_tables_exists($db, true);
	}

	/**
	 * @depends test_install_for_current_blog
	 */
	public function test_uninstall_for_current_blog() {
		$db = new Debiki_Database();
		$db->uninstall_for_current_blog();
		$this->assert_tables_exists($db, false);
	}

	/**
	 * @depends test_uninstall_for_current_blog
	 */
	public function test_reinstall_for_current_blog() {
		$db = new Debiki_Database();
		$db->install_for_current_blog(); # is a reinstall
		$this->assert_tables_exists($db, true);
	}

	function assert_tables_exists($db, $exists) {
		$this->assertEquals($exists, table_exists($db->actions_table_name));
	}



	/**
	 * @depends test_reinstall_for_current_blog
	 */
	public function test_counts_zero_ratings() {
		$db = new Debiki_Database();
		$ratings = $db->load_comment_ratings_for_post(1);
		$ratings_comment_1 = $ratings->ratings_for_comment(1);
		$this->assertEquals(0, count($ratings_comment_1));
	}

	/**
	 * @depends test_reinstall_for_current_blog
	 */
	public function test_gets_no_sort_score() {
		$db = new Debiki_Database();
		$ratings = $db->load_comment_ratings_for_post(1);
		$score = $ratings->sort_score_for_comment(1);
		$this->assertEquals(0, $score);
	}


	/**
	 * @depends test_reinstall_for_current_blog
	 */
	public function test_insert_first_rating() {
		$db = new Debiki_Database();
		$rating = $this->make_first_test_rating();
		$new_action_id = $db->insert_comment_action($rating);
		$this->assertEquals(1, $new_action_id);
	}

	function make_first_test_rating() {
		return Comment_Rating::create()->post_id(1)->comment_id(1)
				->actor_ip('1.2.3.4.')->actor_cookie('coookie_abcd')
				->liked_it(true);
	}


	/**
	 * @depends test_insert_first_rating
	 */
	public function test_gets_sort_score_1() {
		$db = new Debiki_Database();
		$ratings = $db->load_comment_ratings_for_post(1);
		$score = $ratings->sort_score_for_comment(1);
		$this->assertEquals(1, $score);
	}

	/**
	 * @depends test_insert_first_rating
	 */
	public function test_get_back_same_first_rating() {
		$db = new Debiki_Database();
		$ratings = $db->load_comment_ratings_for_post(1);
		$ratings_comment_1 = $ratings->ratings_for_comment(1);

		$this->assertEquals(1, count($ratings_comment_1));
		$rating_loaded = $ratings_comment_1[0];

		$rating_saved = $this->make_first_test_rating();
		$rating_saved->action_id(1);
		$rating_saved->creation_date_utc($rating_loaded->creation_date_utc());
		$this->assertEquals($rating_saved, $rating_loaded);
	}


	/**
	 * A contrived example using some WordPress functionality
	 */
	/*
	public function testPostTitle() {
		// This will simulate running WordPress' main query.
		// See wordpress-tests/lib/testcase.php
		$this->go_to('http://example.org/?p=1');

		// Now that the main query has run, we can do tests that
		// are more functional in nature
		global $wp_query;
		$post = $wp_query->get_queried_object();
		$this->assertEquals('Hello world!', $post->post_title );
	}*/
}
