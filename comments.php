<?php/**
 * Copyright (c) 2012 Kaj Magnus Lindberg (born 1979)
 *
 * Parts Copyright (c) 2011 by the contributors (to WordPress and (?) b2,
 * some of the people mentioned here, I (KajMagnus) suppose:
 *   http://codex.wordpress.org/Copyright_Holders  )
 *
 * License: GPL2.
 *
 * Based on [wordpress-3.3.2]/wp-content/themes/twentyeleven/comments.php.
 */?>

<div id="comments" class="dw-t dw-ar-t dw-depth-0 dw-hor dw-svg-gparnt">
	<?php if ( post_password_required() ) : ?>
		<p class="nopassword"><?php _e( 'This post is password protected. Enter the password to view any comments.', 'twentyeleven' ); ?></p>
	</div><!-- #comments -->
	<?php
			/* Stop the rest of comments.php from being processed,
			 * but don't kill the script entirely -- we still have
			 * to fully load the template.
			 */
			return;
		endif;
	?>

	<?php // You can start editing here -- including this comment! ?>

	<?php if ( have_comments() ) : ?>
		<h2 id="comments-title">
			<?php
			printf(_n( 'One thought on &ldquo;%2$s&rdquo;',
						'%1$s thoughts on &ldquo;%2$s&rdquo;',
						get_comments_number(), 'twentyeleven' ),
					number_format_i18n( get_comments_number() ),
					'<span>' . get_the_title() . '</span>' );
			?>
		</h2>

		<div class='dw-t-vspace'></div>
		<ol class="commentlist dw-res ui-helper-clearfix">
			<?php debiki_list_comments(); ?>
		</ol>

	<?php
	# If no comments and comments are closed, leave a note.
	# Skip on pages or post types that do not support comments.
	#
	elseif (!comments_open() && !is_page() &&
			post_type_supports(get_post_type(), 'comments')) : ?>
		<p class="nocomments"><?php _e('Comments are closed.', 'twentyeleven'); ?></p>
	<?php endif; ?>

	<?php comment_form(); ?>

</div>


<?php
/**
 * HTML comment list class.
 *
 * @package WordPress
 * @uses Walker
 * @since 2.7.0
 */
class Debiki_Walker_Comment extends Walker {
	/**
	 * @see Walker::$tree_type
	 * @since 2.7.0
	 * @var string
	 */
	var $tree_type = 'comment';

	/**
	 * @see Walker::$db_fields
	 * @since 2.7.0
	 * @var array
	 */
	var $db_fields = array ('parent' => 'comment_parent', 'id' => 'comment_ID');

	/**
	 * @see Walker::start_lvl()
	 * @since 2.7.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int $depth Depth of comment.
	 * @param array $args Ignored.
	 */
	function start_lvl(&$output, $depth, $args) {
		$depth++;
		$GLOBALS['comment_depth'] = $depth;
		$is_root_reply = $depth === 1;
		$horiz_clearfix = $is_root_reply ? ' ui-helper-clearfix' : '';
		echo "<ol class='children dw-res{$horiz_clearfix}'>\n";
	}

	/**
	 * @see Walker::end_lvl()
	 * @since 2.7.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int $depth Depth of comment.
	 * @param array $args Ignored.
	 */
	function end_lvl(&$output, $depth, $args) {
		$GLOBALS['comment_depth'] = $depth + 1;  # not -1? weird?
		echo "</ol>\n";
	}

	/**
	 * This function is designed to enhance Walker::display_element() to
	 * display children of higher nesting levels than selected inline on
	 * the highest depth level displayed. This prevents them being orphaned
	 * at the end of the comment list.
	 *
	 * Example: max_depth = 2, with 5 levels of nested content.
	 * 1
	 *  1.1
	 *    1.1.1
	 *    1.1.1.1
	 *    1.1.1.1.1
	 *    1.1.2
	 *    1.1.2.1
	 * 2
	 *  2.2
	 *
	 */
	function display_element( $element, &$children_elements, $max_depth, $depth=0, $args, &$output ) {

		if ( !$element )
			return;

		$id_field = $this->db_fields['id'];
		$id = $element->$id_field;

		parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );

		// If we're at the max depth, and the current element still has children, loop over those and display them at this level
		// This is to prevent them being orphaned to the end of the list.
		if ( $max_depth <= $depth + 1 && isset( $children_elements[$id]) ) {
			foreach ( $children_elements[ $id ] as $child )
				$this->display_element( $child, $children_elements, $max_depth, $depth, $args, $output );

			unset( $children_elements[ $id ] );
		}

	}

	/**
	 * @see Walker::start_el()
	 * @since 2.7.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $comment Comment data object.
	 * @param int $depth Depth of comment in reference to parents.
	 * @param array $args
	 */
	function start_el(&$output, $comment, $depth, $args) {
		$depth++;
		$GLOBALS['comment_depth'] = $depth;

		debiki_render_comment($comment, $args, $depth);
	}

	/**
	 * @see Walker::end_el()
	 * @since 2.7.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $comment
	 * @param int $depth Depth of comment.
	 * @param array $args Ignored.
	 */
	function end_el(&$output, $comment, $depth, $args) {
		echo "</li>\n";
	}

}


function debiki_list_comments($args = array(), $comments = null ) {
	global $wp_query, $comment_alt, $comment_depth, $comment_thread_alt, $overridden_cpage, $in_comment_loop;

	$in_comment_loop = true;

	$comment_alt = $comment_thread_alt = 0;
	$comment_depth = 1;

	$defaults = array('walker' => null, 'max_depth' => '', 'type' => 'all',
		'page' => '', 'per_page' => '', 'avatar_size' => 32, 'reverse_top_level' => null, 'reverse_children' => '');

	$r = wp_parse_args( $args, $defaults );

	// Figure out what comments we'll be looping through ($_comments)
	if ( null !== $comments ) {
		$comments = (array) $comments;
		if ( empty($comments) )
			return;
		if ( 'all' != $r['type'] ) {
			$comments_by_type = &separate_comments($comments);
			if ( empty($comments_by_type[$r['type']]) )
				return;
			$_comments = $comments_by_type[$r['type']];
		} else {
			$_comments = $comments;
		}
	} else {
		if ( empty($wp_query->comments) )
			return;
		if ( 'all' != $r['type'] ) {
			if ( empty($wp_query->comments_by_type) )
				$wp_query->comments_by_type = &separate_comments($wp_query->comments);
			if ( empty($wp_query->comments_by_type[$r['type']]) )
				return;
			$_comments = $wp_query->comments_by_type[$r['type']];
		} else {
			$_comments = $wp_query->comments;
		}
	}

	if ( '' === $r['per_page'] && get_option('page_comments') )
		$r['per_page'] = get_query_var('comments_per_page');

	if ( empty($r['per_page']) ) {
		$r['per_page'] = 0;
		$r['page'] = 0;
	}

	if ( '' === $r['max_depth'] ) {
		if ( get_option('thread_comments') )
			$r['max_depth'] = get_option('thread_comments_depth');
		else
			$r['max_depth'] = -1;
	}

	if ( '' === $r['page'] ) {
		if ( empty($overridden_cpage) ) {
			$r['page'] = get_query_var('cpage');
		} else {
			$threaded = ( -1 != $r['max_depth'] );
			$r['page'] = ( 'newest' == get_option('default_comments_page') ) ? get_comment_pages_count($_comments, $r['per_page'], $threaded) : 1;
			set_query_var( 'cpage', $r['page'] );
		}
	}
	// Validation check
	$r['page'] = intval($r['page']);
	if ( 0 == $r['page'] && 0 != $r['per_page'] )
		$r['page'] = 1;

	if ( null === $r['reverse_top_level'] )
		$r['reverse_top_level'] = ( 'desc' == get_option('comment_order') );

	extract( $r, EXTR_SKIP );

	if ( empty($walker) )
		$walker = new Debiki_Walker_Comment;

	$walker->paged_walk($_comments, $max_depth, $page, $per_page, $r);
	$wp_query->max_num_comment_pages = $walker->max_pages;

	$in_comment_loop = false;
}


/**
 * Based on function twentyeleven_comment(..) in
 * wp-content/themes/twentyeleven/functions.php.
 *
 * @param type $comment
 * @param type $args
 * @param type $depth
 */
function debiki_render_comment( $comment, $args, $depth ) {
	$GLOBALS['comment'] = $comment;
	switch ( $comment->comment_type ) :
		case 'pingback' :
		case 'trackback' :
	?>
	<li class="post pingback">
		<p><?php _e( 'Pingback:', 'twentyeleven' ); ?> <?php comment_author_link(); ?><?php edit_comment_link( __( 'Edit', 'twentyeleven' ), '<span class="edit-link">', '</span>' ); ?></p>
	<?php
			break;
		default :
	?>
	<li <?php comment_class('dw-t dw-depth-'.$depth); ?> id="li-comment-<?php comment_ID(); ?>">
		<article id="comment-<?php comment_ID(); ?>" class="comment dw-p">
			<footer class="comment-meta dw-p-hd">
				<div class="comment-author vcard">
					<?php
						$avatar_size = 68;
						if ( '0' != $comment->comment_parent )
							$avatar_size = 39;

						echo get_avatar( $comment, $avatar_size );

						/* translators: 1: comment author, 2: date and time */
						printf( __( '%1$s on %2$s <span class="says">said:</span>', 'twentyeleven' ),
							sprintf( '<span class="fn">%s</span>', get_comment_author_link() ),
							sprintf( '<a href="%1$s"><time pubdate datetime="%2$s">%3$s</time></a>',
								esc_url( get_comment_link( $comment->comment_ID ) ),
								get_comment_time( 'c' ),
								/* translators: 1: date, 2: time */
								sprintf( __( '%1$s at %2$s', 'twentyeleven' ), get_comment_date(), get_comment_time() )
							)
						);
					?>

					<?php edit_comment_link( __( 'Edit', 'twentyeleven' ), '<span class="edit-link">', '</span>' ); ?>
				</div><!-- .comment-author .vcard -->

				<?php if ( $comment->comment_approved == '0' ) : ?>
					<em class="comment-awaiting-moderation"><?php _e( 'Your comment is awaiting moderation.', 'twentyeleven' ); ?></em>
					<br />
				<?php endif; ?>

			</footer>

			<div class="comment-content dw-p-bd"><?php comment_text(); ?></div>

			<div class="reply">
				<?php comment_reply_link( array_merge( $args, array( 'reply_text' => __( 'Reply <span>&darr;</span>', 'twentyeleven' ), 'depth' => $depth, 'max_depth' => $args['max_depth'] ) ) ); ?>
			</div><!-- .reply -->
		</article><!-- #comment-## -->

	<?php
			break;
	endswitch;
}


?>