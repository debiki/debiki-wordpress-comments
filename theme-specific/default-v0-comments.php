<?php
/**
 * Copyright (c) 2012 Kaj Magnus Lindberg (born 1979)
 * Licensed: GPL v2 or later
 *
 * Parts Copyright (c) 2011 by the contributors (to WordPress and (?) b2,
 * some of the people mentioned here, I (KajMagnus, see below) suppose:
 *   http://codex.wordpress.org/Copyright_Holders  )
 * The contributors' code is licensed under GNU General Public License v2 or later,
 * see: wp-content/themes/twentyeleven/style.css
 *
 * The contributors (see above) have copyright on the messages inside `_e()` and
 * `_n()` and `__()`.
 */


#===========================================================
# Comment section template
#===========================================================

?>

<section class='comments dw-wp-comment-section'>

<?php
if (post_password_required()) :
?>
	<p class='nopassword'>
	<?php _e('This post is password protected. Enter the password to view any comments.', 'twentyeleven'); ?>
	</p>
</section>
<?php
	return;
endif;

if (have_comments() ) :
?>

	<h2 id='dw-wp-comments-title'>
		<?php
		$formatStr = _n(
				'One thought on &ldquo;%2$s&rdquo;',
				'%1$s thoughts on &ldquo;%2$s&rdquo;',
				get_comments_number(),
				'twentyeleven');
		$prettyCommentCount = number_format_i18n(get_comments_number());
		printf($formatStr, $prettyCommentCount,
				'<span>'.get_the_title().'</span>');
		?>
	</h2>

	<div class='debiki dw-debate'>
	<div id='dw-t-1' class='dw-t dw-depth-0 dw-hor dw-svg-gparnt'>
	<div class='dw-p'></div>
	<div class='dw-t-vspace'></div>
	<ol class='dw-res'>
		<?php
		comment_form();
		debiki_list_comments('debiki_default_comment');
		?>
	</ol>
	</div>
	</div>
	<?php
endif;

# If no comments and comments are closed, leave a note.
# Skip on pages or post types that do not support comments.
if (!comments_open() && !is_page() &&
		post_type_supports(get_post_type(), 'comments')) :
?>
	<p class='nocomments'>
		<?php _e('Comments are closed.', 'twentyeleven'); ?>
	</p>
<?php
endif; ?>

</section>
<?php



#===========================================================
# Single comment template
#===========================================================

/**
 * Similar to Twenty Eleven's `twentyeleven_comment()`, but adds some new tags
 * and CSS classes and a fold thread button: [–].
 *
 * Initially copied from function `twentyeleven_comment()` in
 * wp-content/themes/twentyeleven/functions.php. I've tried to do few edits,
 * so a diff should give meaningful results.
 */
function debiki_default_comment( $comment, $args, $depth ) {
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
	<?php /*
	Don't add `comment_class` to the <li>; add it to the <article> instead.
	Otherwise Themes that color the background for .bypostauthor comments would paint
   the whole <li> — which doesn't look nice, and is not needed,
	with Debiki's two dimensional layout.
	*/?>
	<li <?php echo "class='dw-t dw-depth-$depth'" ?> id="li-comment-<?php comment_ID(); ?>">
		<a class="dw-z">[–]</a>
		<article id="comment-<?php comment_ID(); ?>"
				class="<?php echo debiki_comment_classes(), ' dw-p' ?>" >
			<footer class="dw-p-hd">
				<div>
					<?php
					$avatar_size = $comment->comment_parent === '0' ? 69 : 39;
					echo get_avatar($comment, $avatar_size);
					?>
					<div class="dw-p-by"><?php comment_author_link() ?></div>
					<?php /*
					COULD find out which <time> class is "best"? `pubdate` or `published`?
					*/?>
					<time class='published' datetime='<?php get_comment_time('c') ?>'>
						<?php comment_date() ?>
					</time>
				</div>

				<?php if ( $comment->comment_approved == '0' ) : ?>
					<em class="comment-awaiting-moderation">
						<?php _e( 'Your comment is awaiting moderation.', 'twentyeleven' ); ?>
					</em>
				<?php endif; ?>
			</footer>

			<div class="dw-p-bd">
				<?php comment_text() ?>
			</div>

			<span class="dw-wp-actions">
				<span class="dw-wp-reply-link" <?php echo debiki_reply_link_data($comment); ?> >
					<?php comment_reply_link( array_merge( $args, array( 'reply_text' => __( 'Reply <span>&darr;</span>', 'twentyeleven' ), 'depth' => $depth, 'max_depth' => $args['max_depth'] ) ) ); ?>
				</span>
				<span class="dw-wp-edit-link">
					<?php edit_comment_link(
							__( 'Edit', 'twentyeleven' ),
							'<span class="edit-link">', '</span>' ); ?>
				</span>
				<?php /*
				COULD include comment permalink:
					esc_url( get_comment_link( $comment->comment_ID ) )
				*/?>
			</span>
		</article>

	<?php
			break;
	endswitch;
}

?>