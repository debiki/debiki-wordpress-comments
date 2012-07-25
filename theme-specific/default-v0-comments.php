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

<section class='<?php echo apply_filters('debiki_comment_section_classes', '') ?>'>

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

if (comments_open()) :
?>

	<?php if (have_comments()): ?>
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
	<?php endif; ?>

	<div class='debiki dw-debate dw-page' data-dw_wp_post_id='<?php the_ID() ?>'>
	<div id='dw-t-1' class='dw-t dw-depth-0 dw-hor dw-svg-gparnt'>
	<div class='dw-p'></div>
	<div class='dw-t-vspace'></div>
	<ol class='dw-res'>
		<li><?php comment_form(); ?></li>
		<?php \Debiki\debiki_list_comments('debiki_default_comment'); ?>
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


<?php
# An invisible rate comment form, posted by Javascript.
# It can be posted to anywhere — a plugin action hook checks for
# the presence of the debiki-action-nonce input.
?>
<form id="dw-wp-rate-comment-form" action="?rate-comment" method="post">
	<input type="text" name="debiki-action-nonce" value="SECURITY TODO">
	<input type="text" name="post-id" value="<?php the_ID() ?>">
	<input type="text" name="comment-id" value="?">
	<input type="text" name="vote-value" value="?">
</form>


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
	$comment_id = get_comment_ID();
	$comment_ratings = $args['debiki_comment_ratings'];

	# Only render special HTML, with .dw-wp-my-vote classes, for logged in
	# registered users — otherwise HTML caching plugins might cache the
	# wrong version of the page, with vote info for someone else.
	# (I'll store my-vote-info in browser local storage,
	# for non-registered users?)

	$user = wp_get_current_user();
	# (This: wp_get_current_commenter() reads name and email cookies)

	$vote_counts = $comment_ratings->count_ratings_for_comment(
			$comment_id, $user->exists() ? $user->ID : false);

	$my_upvote_classes = $vote_counts->users_upvote_count > 0 ?
			'dw-wp-my-vote' : '';

	$my_downvote_classes = $vote_counts->users_downvote_count > 0 ?
			'dw-wp-my-vote' : '';

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
				class="<?php echo \Debiki\debiki_comment_classes(), ' dw-p' ?>"
				<?php echo \Debiki\debiki_comment_data_attrs($comment) ?> >
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
				<span class="dw-wp-reply-link">
					<?php comment_reply_link( array_merge( $args, array( 'reply_text' => __( 'Reply', 'twentyeleven' ), 'depth' => $depth, 'max_depth' => $args['max_depth'] ) ) ); ?>
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
				<span class="dw-wp-rate-links">
					<span class="dw-wp-vote-up <?php echo $my_upvote_classes ?>">
						<span class="dw-wp-vote-count" title="Number of up votes">
							<?php echo $vote_counts->upvote_count ?>
						</span>
						<a class="dw-wp-vote-link" title="Vote up"></a>
					</span>
					<span class="dw-wp-vote-down <?php echo $my_downvote_classes ?>">
						<a class="dw-wp-vote-link" title="Vote down"></a>
						<span class="dw-wp-vote-count" title="Number of down votes">
							<?php echo $vote_counts->downvote_count ?>
						</span>
					</span>
				</span>
			</span>
		</article>

	<?php
			break;
	endswitch;
}

?>