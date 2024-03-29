<?php
/**
 * Copyright (c) 2012 Kaj Magnus Lindberg (born 1979)
 * License: GPL v2 or later
 */

/*
See: http://codex.wordpress.org/Creating_Tables_with_Plugins
*/

/*
 * Concerning action_value_tag and action_value_text:
 * action_value_tag stores any first rating tag.
 * Additional tags are concatenated and stored in $action_value_text.
 * — This is denormalization, and improves performance, and is simpler
 * I think, since I won't have to bother aboout a separate
 * rating tags table. But makes it much harder to write, and slower
 * to exequte, certain probably-never-needed SQL queries.
 * The reason the very first tag is stored in a separate column,
 * is that this makes it possible to gather statistics on e.g. which
 * tags are used the most. So this is a mixture of normalization for
 * some ad hoc queries, and denormalization for high performance.
 * (You could search the web for "denormalization performance".)
 */

namespace Debiki;

require_once 'debiki-comment-ratings.php';


function table_exists($table_name) {
	global $wpdb;
	$tables = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
	return $tables == $table_name;
}


function index_exists($table_name, $index_name) {
	global $wpdb;
	$index_info = $wpdb->get_row(
			"show index from $table_name where key_name = '$index_name'");
	assert($index_info === null || $index_info->Key_name === $index_name);
	return $index_info !== null;
}


class Debiki_Database {


	/**
	 * For DoS attack prevention. Not vote fraud detection — each row
	 * might not count as a rating, later when sorting comments by good/bad
	 * ratings.
	 */
	const max_rating_rows_per_comment_and_ip = 50;

	function __construct() {
		global $wpdb;

		$this->db_version = '0.00.01';

		# dw0 means Debate Wiki version 0.
		$prefix = $wpdb->prefix."dw0_";

		$this->actions_table_name = $prefix.'comment_actions';

		$this->actions_table__post_index_name =
				$prefix.'comment_actions__post_ix';
		$this->actions_table__comment_index_name =
				$prefix.'comment_actions__comment_ix';
		$this->actions_table__ip_index_name =
				$prefix.'comment_actions__ip_ix';

		$this->actions_table__comment_fk_name =
				$prefix.'comment_actions__comment_fk';
		$this->actions_table__post_fk_name =
				$prefix.'comment_actions__post_fk';

		$this->wp_comments_table_name = $wpdb->prefix.'comments';
		$this->wp_posts_table_name = $wpdb->prefix.'posts';
	}


	/**
	 * Executes a database query. If it fails, checks if the reason is
	 * that we haven't installed any database tables. If so, installs them
	 * and runs the query again.
	 *
	 * The reason tables might not have been installed is that this might
	 * be a WordPress MultiSite / Network installation, and, for simplicity,
	 * I create tables on demand, here. Then I don't have to check for
	 * both plugin activation *and* the addition of a new blog/website
	 * *after* this plugin has been activated.
	 *
	 * This lazy install does not affect performance — we check for
	 * table existance only once, then never again.
	 */
	function exec_query_install_if_needed($query_func) {
		global $wpdb;
		$result = $query_func();
		if ($wpdb->last_error) {
			$this->install_if_needed();
			$result = $query_func();
		}
		return $result;
	}


	function install($network_wide) {
		global $wpdb;
		if ($network_wide) {
			# UNTESTED
			# `get_blog_list` is deprecated, but there's currently no replacement.
			$blog_list = get_blog_list(0, 'all');
			foreach ($blog_list as $blog) {
				switch_to_blog($blog['blog_id']);
				$this->install_for_current_blog();
			}
			switch_to_blog($wpdb->blogid);
		} else {
			$this->install_for_current_blog();
		}
	}


	function install_for_current_blog() {
		global $wpdb;

		# Setup table creation SQL.

		$charset_collate = '';
		if (!empty ($wpdb->charset))
			$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
		if (!empty ($wpdb->collate))
			$charset_collate .= " COLLATE {$wpdb->collate}";

		# `action_id` is unique, but not used as primary key (PK),
		# because there is no need for an index on that column only.
		# Instead, (post_id, action_id) is the PK (and used in queries).
		# Note: If you change any column name, please update the
		# Action_Locator.located_by constants.
		$actions_table_sql = "CREATE TABLE $this->actions_table_name (
			action_id bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
			action_type varchar(20) NOT NULL,
			action_value_byte tinyint,
			action_value_tag varchar(50),
			action_value_text text,
			creation_date_utc datetime NOT NULL,
			modification_date_utc datetime default null,
			modification_count int NOT NULL default 0,
			post_id bigint(20) unsigned NOT NULL,
			comment_id bigint(20) unsigned NOT NULL,
			actor_user_id bigint(20) unsigned,
			actor_cookie varchar(100),
			actor_ip varchar(100),
			actor_name tinytext,
			actor_email varchar(100),
			CONSTRAINT $this->actions_table__comment_fk_name
			FOREIGN KEY (comment_id)
			REFERENCES $this->wp_comments_table_name (comment_ID)
			ON DELETE CASCADE,
			CONSTRAINT $this->actions_table__post_fk_name
			FOREIGN KEY (post_id)
			REFERENCES $this->wp_posts_table_name (ID)
			ON DELETE CASCADE
			) $charset_collate;";

		# Index for looking up everything on one page.
		$actions_table__post_index_sql= "
			CREATE INDEX $this->actions_table__post_index_name
			ON $this->actions_table_name (post_id);";

		# Index for looking up all actions related to one comment.
		$actions_table__comment_index_sql= "
			create index $this->actions_table__comment_index_name
			on $this->actions_table_name (comment_id);";

		# Index on IP address.
		$actions_table__ip_index_sql = "
			CREATE INDEX $this->actions_table__ip_index_name
			ON $this->actions_table_name (actor_ip);";

		# Create tables.

		# (Don't use dbDelta please. — Using it is like praying for bugs?)
		# (Check for existance of each table and index, in case the script has
		# previously been abruptly terminated and not all tables were created.)

		if (!table_exists($this->actions_table_name))
			$wpdb->query($actions_table_sql);

		if (!index_exists($this->actions_table_name,
				$this->actions_table__post_index_name))
			$wpdb->query($actions_table__post_index_sql);

		if (!index_exists($this->actions_table_name,
				$this->actions_table__comment_index_name))
			$wpdb->query($actions_table__comment_index_sql);

		if (!index_exists($this->actions_table_name,
				$this->actions_table__ip_index_name))
			$wpdb->query($actions_table__ip_index_sql);

		add_option('debiki_wp_comments_db_version', $this->db_version);

		## In the future, upgrade tables? Somewhere, something like:
		# $installed_verison = get_option('debiki_db_version', $db_version);
		# if ($installed_verison != $db_version) {
		#	$sql = ...
		#	dbDelta($sql);
		#	update_option('debiki_db_version', $db_version);
		# }
	}


	function uninstall_for_current_blog() {
		global $wpdb;
		$wpdb->query("DROP TABLE $this->actions_table_name;");
	}


	function update_comment_action($earlier_version, $action) {
		global $wpdb;

		$values = array();
		$values[] = $action->action_value_byte();
		$values[] = $action->comment_id();

		# Specify which row to update.

		if (isset($earlier_version->same_by_user_id[0])) {
			$where_clause = "actor_user_id = %d";
			$values[] = $earlier_version->same_by_user_id[0]->actor_user_id();
		}
		else if (isset($earlier_version->same_by_cookie[0])) {
			$where_clause = "actor_cookie = %s";
			$values[] = $earlier_version->same_by_cookie[0]->actor_cookie();
		}
		else if (isset($earlier_version->same_by_ip[0])) {
			$where_clause = "actor_ip = %s";
			$values[] = $earlier_version->same_by_ip[0]->actor_ip();
		}
		else {
			assert(false);
		}

		# Update the row.

		# (Right now, only +1/-1 ratings supported, so only
		# action_value_byte is updated.)
		$sql = "update $this->actions_table_name set
					action_value_byte = %d,
					modification_date_utc = UTC_TIMESTAMP(),
					modification_count = modification_count + 1
				where comment_id = %d and ".$where_clause;

		$wpdb->query($wpdb->prepare($sql, $values));

		$num_rows_updated = $wpdb->rows_affected;
		return $num_rows_updated;
	}


	/**
	 * Increments/decrements action_value_byte for a catch-all rating
	 * for $rating->actor_ip.
	 *
	 * (Later when we sort comments by ratings, this catch-all rating
	 * will perhaps count as only one rating although it represents
	 * rather many rating submission attempts, from a single IP.)
	 */
	# function update_catch_all_by_ip_rating($rating) {
	# }


	function insert_comment_action($action) {
		global $wpdb;

		# TODO before release: Insert NULL not empty strings.

		$sql = "insert into $this->actions_table_name (
					action_type,
					action_value_byte,
					action_value_tag,
					action_value_text,
					creation_date_utc,
					post_id,
					comment_id,
					actor_user_id,
					actor_cookie,
					actor_ip,
					actor_name,
					actor_email
				) values (
					%s, %d, null, null, UTC_TIMESTAMP(),
					%d, %d, %d, %s, %s, null, null
				)";

		$wpdb->query($wpdb->prepare($sql, array(
				# $action->action_id, — auto incremented
				$action->action_type(),
				$action->action_value_byte(),
				#$action->action_value_tag(), — null, for now
				#$action->action_value_text(), — null, for now
				# $action->creation_date_utc, — now(), instead
				$action->post_id(),
				$action->comment_id(),
				$action->actor_user_id(),
				$action->actor_cookie(),
				$action->actor_ip())));
				#$action->actor_name(), — null
				#$action->actor_email(), — null,

		$new_action_id = $wpdb->insert_id;
		return $new_action_id;
	}


	function load_comment_ratings_for_post($post_id) {
		#global $wpdb;
		$sql = "
			select * from $this->actions_table_name
			where post_id = %d";
		return $this->_load_actions($sql, $post_id);
	}


	function load_comment_ratings_for_comment($comment_id) {
		# wp_comments comment_ID is the primary key (post id not needed).
		$sql = "
			select * from $this->actions_table_name
			where comment_id = %d";
		return $this->_load_actions($sql, $comment_id);
	}


	private function _load_actions($sql, $values) {
		global $wpdb;
		$action_rows = $wpdb->get_results($wpdb->prepare($sql, $values));
		$this->_convert_strings_to_ints($action_rows);
		return Comment_Ratings::from_db_rows(& $action_rows);
	}


	private function _convert_strings_to_ints(& $db_rows) {
		# WordPress loads everything as strings, so we need to
		# manually convert the appropriate colums to integres.
		# (Is there no better way?)
		foreach ($db_rows as & $row) {
			$row->action_id = intval($row->action_id);
			$row->action_value_byte =intval($row->action_value_byte);
			$row->modification_count = intval($row->modification_count);
			$row->post_id = intval($row->post_id);
			$row->comment_id = intval($row->comment_id);
			$row->actor_user_id = intval($row->actor_user_id);
		}
	}
}
