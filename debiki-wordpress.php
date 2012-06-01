<?php
/*
Plugin Name: Debiki for Wordpress
Plugin URI: http://wordpress.debiki.com
Description: A more efficient commenting system that hopefully contributes to fruitful discussions.
Version: 0.00.01
Author: Kaj Magnus Lindberg
Author URI: http://kajmagnus.debiki.se
License: GPLv2 or later
*/

add_filter('comments_template', 'debiki_comments');
function debiki_comments($comments) {
  return dirname(__FILE__) . '/comments.php';
}




/*
# Makes Disqus work on my local network.
add_action('wp_head', 'my_custom_js');
function my_custom_js() {
  echo "<script>var disqus_developer = 1;\n</script>\n";
}
*/

?>
