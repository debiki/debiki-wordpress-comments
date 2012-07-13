<?php
/**
 * Copyright (c) 2012 Kaj Magnus Lindberg (born 1979)
 * License: GPL v2 or later
 */

/*
See: http://codex.wordpress.org/Creating_Tables_with_Plugins
*/

namespace Debiki;

require_once 'debiki-comment-ratings.php';


function table_exists($table_name) {
	global $wpdb;
	$tables = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
	return $tables == $table_name;
}


class Debiki_Database {


	function __construct() {
		global $wpdb;

		$this->db_version = '0.00.01';

		# dw0 means Debate Wiki version 0.
		$prefix = $wpdb->prefix."dw0_";

		$this->actions_table_name = $prefix.'comment_actions';
		$this->tags_table_name = $prefix.'comment_action_tags';
		$this->log_table_name = $prefix.'comment_action_log';

		$this->actions_table__post_index_name =
				$prefix.'comment_actions__comment_ix';
		$this->actions_table__ip_index_name =
				$prefix.'comment_actions__ip_ix';

		$this->actions_table__comment_fk_name =
				$prefix.'comment_actions__comment_fk';
		$this->actions_table__post_fk_name =
				$prefix.'comment_actions__post_fk';

		$this->tags_table__action_fk_name =
				$prefix.'comment_actions__tags_action_fk';

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
		$actions_table_sql = "CREATE TABLE $this->actions_table_name (
			action_id bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
			action_type varchar(20) NOT NULL,
			action_value_byte tinyint,
			action_value_text text,
			creation_date_utc datetime NOT NULL,
			post_id bigint(20) unsigned NOT NULL,
			comment_id bigint(20) unsigned NOT NULL,
			actor_name tinytext,
			actor_email varchar(100),
			actor_ip varchar(100),
			actor_user_id bigint(20) unsigned,
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

		# Index for restricting votes per IP.
		$actions_table__ip_index_sql = "
			CREATE INDEX $this->actions_table__ip_index_name
			ON $this->actions_table_name (actor_ip);";

		$tags_table_sql = "CREATE TABLE $this->tags_table_name (
			post_id bigint(20) unsigned NOT NULL,
			action_id bigint(20) unsigned NOT NULL,
			action_tag varchar(50) NOT NULL,
			CONSTRAINT $this->tags_table__action_fk_name
			FOREIGN KEY (post_id, action_id)
			REFERENCES $this->actions_table_name (post_id, action_id)
			ON DELETE CASCADE
			) $charset_collate;";

		$log_table_sql = "CREATE TABLE $this->log_table_name (
			action_log_date datetime NOT NULL,
			action_log_text varchar(2000) NOT NULL
			) $charset_collate;";

		# Create tables.

		# (Don't use dbDelta please. — Using it is like praying for bugs?)

		# (Check for existance of each table, in case the script has
		# previously been abruptly terminated and not all tables were created.)


		if (!table_exists($this->actions_table_name))
			$wpdb->query($actions_table_sql);

		if (!table_exists($this->tags_table_name))
			$wpdb->query($tags_table_sql);

		if (!table_exists($this->log_table_name))
			$wpdb->query($log_table_sql);


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
		$wpdb->query("DROP TABLE $this->tags_table_name;");
		$wpdb->query("DROP TABLE $this->log_table_name;");
	}


	function save_comment_rating($rating) {
		$action = $rating;
		$action->action_type = 'C';
		$action->action_value_text = null;
		return $this->save_comment_action($action);
	}


	function save_comment_action($action) {
		global $wpdb;

		assert(Debiki_Comment_Rating::is_valid($action));
		# $action->creation_date = date("Y-m-d H:i:s", $datetime);
		# ? $wpdb->query( 'SET autocommit = 0;' );
		# $wpdb->query( 'START TRANSACTION;' );

		$sql = "insert into $this->actions_table_name (
					action_type,
					action_value_byte,
					action_value_text,
					creation_date_utc,
					post_id,
					comment_id,
					actor_name,
					actor_email,
					actor_ip,
					actor_user_id
				) values (
					%s, %d, %s, UTC_TIMESTAMP(), %d, %d, %s, %s, %s, %d
				)";

		$new_action_id = $wpdb->query($wpdb->prepare($sql, array(
					 # $action->action_id, — auto incremented
					 $action->action_type,
					 $action->action_value_byte,
					 $action->action_value_text,
					 # $action->creation_date, — now(), instead
					 $action->post_id,
					 $action->comment_id,
					 $action->actor_name,
					 $action->actor_email,
					 $action->actor_ip,
					 $action->actor_user_id)));

		return $new_action_id;
	}


	function load_comment_ratings_for_post($post_id) {
		global $wpdb;
		$sql = "
			select * from $this->actions_table_name
			where post_id = %d";
		$action_rows = $wpdb->get_results($wpdb->prepare($sql, $post_id));
		return Debiki_Comment_Ratings::from_action_rows(& $action_rows);
	}



/*
	if ( $user_ID) {
		$comments = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->comments WHERE comment_post_ID = %d AND (comment_approved = '1' OR ( user_id = %d AND comment_approved = '0' ) )  ORDER BY comment_date_gmt", $post->ID, $user_ID));
	} else if ( empty($comment_author) ) {
		$comments = get_comments( array('post_id' => $post->ID, 'status' => 'approve', 'order' => 'ASC') );
	} else {
		$comments = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->comments WHERE comment_post_ID = %d AND ( comment_approved = '1' OR ( comment_author = %s AND comment_author_email = %s AND comment_approved = '0' ) ) ORDER BY comment_date_gmt", $post->ID, wp_specialchars_decode($comment_author,ENT_QUOTES), $comment_author_email));
	}

*/

}

