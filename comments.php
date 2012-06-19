<?php/**
 * Copyright (c) 2012 Kaj Magnus Lindberg (born 1979)
 *
 * Parts Copyright (c) 2011 by the contributors (to WordPress and (?) b2,
 * some of the people mentioned here, I (KajMagnus) suppose:
 *   http://codex.wordpress.org/Copyright_Holders  )
 *
 * License: GPL2 or later
 *
 * Based on [wordpress-3.3.2]/wp-content/themes/twentyeleven/comments.php.
 */?>

<div class='debiki dw-debate'>
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

		<!-- Makes debiki.js find the parent().children('.dw-t-vspace') and create an SVG root. -->
		<div class='dw-p'></div>
		<div class='dw-t-vspace'></div>
		<ol class="commentlist dw-res ui-helper-clearfix">
			<?php
			comment_form();
			wp_list_comments(array(
					'walker' => new Debiki_Walker_Comment,
					'callback' => 'debiki_render_comment',
					'style' => 'ol'));
			?>
		</ol>

	<?php
	# If no comments and comments are closed, leave a note.
	# Skip on pages or post types that do not support comments.
	#
	elseif (!comments_open() && !is_page() &&
			post_type_supports(get_post_type(), 'comments')) : ?>
		<p class="nocomments"><?php _e('Comments are closed.', 'twentyeleven'); ?></p>
	<?php endif; ?>

</div>
</div>


<?php

/**
 * Adds html class attributes required by Debiki's CSS and Javascript.
 */
class Debiki_Walker_Comment extends Walker_Comment {

	function start_lvl(&$output, $depth, $args) {
		$depth++;
		$GLOBALS['comment_depth'] = $depth;
		$is_root_reply = $depth === 1;
		$horiz_clearfix = $is_root_reply ? ' ui-helper-clearfix' : '';
		assert($args['style'] == 'ol');
		echo "<ol class='children dw-res{$horiz_clearfix}'>";
	}

}


/**
 * Adds these classes to comment related tags:
 *	  dw-t, dw-depth-<x>, dw-p, dw-p-hd, dw-p-bd
 *
 * Copied from function twentyeleven_comment(..) in
 * wp-content/themes/twentyeleven/functions.php. I've done only a few minor edits,
 * run a diff to find out.
 *
 * (There's a filter, 'comment_class', that I could use to add 'dw-t' and 'dw-depth-'.
 * However more classes needs to be added elsewhere. And anyway I don't think it's
 * a good idea to depend on code in the Twenty-Eleven theme -- then Debiki's code
 * could suddenly break, should someone upgrade the theme.)
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