<?php
/**
 * Copyright (c) 2012 Konstantin Obenland
 * License: GNU General Public License v3.0
 * (See wp-content/themes/the-bootstrap/style.css )
 *
 * Parts Copyright (c) 2012 Kaj Magnus Lindberg
 * License: GPL v2 or later (for my modifications only)
 *
 * Copied from:
 *   wp-content/themes/the-bootstrap/comments.php
 *
 *
 * I (KajMagnus) have edited the PHP code to output certain Debiki related CSS classes,
 * and some extra tags, and removed next and previous comment page links (which are not
 * needed with Debiki's two dimensional layout).
 */


#===========================================================
# Comment section template
#===========================================================


/**
 *  An action callback, the_bootstrap_comment_form_top(), adds a Twitter Bootstrap
 * CSS class `form-horizontal` to the reply form. This doesn't work well with
 * Debiki's narrow columns, so replace it with Twitter Bootstrap's `form-vertical`
 * instead.
 * See: wp-content/themes/the-bootstrap/functions.php
 */

remove_action('comment_form_top', 'the_bootstrap_comment_form_top');
add_action('comment_form_top', 'debiki_the_bootstrap_comment_form_top');

function debiki_the_bootstrap_comment_form_top() {
	echo '<div class="form-vertical">';
}


/**
 * The comment section.
 * Based on: wp-content/themes/the-bootstrap/comments.php
 */

if ( post_password_required() ) : ?>
	<div id="comments">
		<p class="nopassword"><?php _e( 'This post is password protected. Enter the password to view any comments.', 'the-bootstrap' ); ?></p>
	</div><!-- #comments -->
	<?php
	return;
endif;


if ( have_comments() ) : ?>
	<div id="comments">
		<h2 id="comments-title">
			<?php printf( _n( 'One thought on &ldquo;%2$s&rdquo;', '%1$s thoughts on &ldquo;%2$s&rdquo;', get_comments_number(), 'the-bootstrap' ),
					number_format_i18n( get_comments_number() ), '<span>' . get_the_title() . '</span>' ); ?>
		</h2>

		<div class='debiki dw-debate'>
		<div id="dw-t-1" class="dw-t dw-depth-0 dw-hor dw-svg-gparnt">
		<div class='dw-p'></div>
		<div class='dw-t-vspace'></div>
		<ol class="commentlist unstyled dw-res ui-helper-clearfix">
			<?php
			debiki_the_bootstrap_comment_form();
			debiki_list_comments('debiki_the_bootstrap_comment');
			?>
		</ol><!-- .commentlist .unstyled -->
		</div>
		</div>

	</div><!-- #comments -->
<?php endif;

if ( ! comments_open() AND ! is_page() AND post_type_supports( get_post_type(), 'comments' ) ) : ?>
	<p class="nocomments"><?php _e( 'Comments are closed.', 'the-bootstrap' ); ?></p>
<?php endif;

/**
 * Wraps the comment_form in a function that can be called above, so I don't have
 * to copy paste all this code, from here to above — if I did that, a diff would
 * be harder to interpret.
 *
 * Don't indent the method body or it'll be somewhat harder to interpret a diff.
 */
function debiki_the_bootstrap_comment_form() {
return
comment_form( array(
	'comment_field'			=>	'<div class="comment-form-comment control-group"><label class="control-label" for="comment">' . _x( 'Comment', 'noun', 'the-bootstrap' ) . '</label><div class="controls"><textarea class="span7" id="comment" name="comment" rows="8" aria-required="true"></textarea></div></div>',
	'comment_notes_before'	=>	'',
	'comment_notes_after'	=>	'<div class="form-allowed-tags control-group"><label class="control-label">' . sprintf( __( 'You may use these <abbr title="HyperText Markup Language">HTML</abbr> tags and attributes: %s', 'the-bootstrap' ), '</label><div class="controls"><pre>' . allowed_tags() . '</pre></div>' ) . '</div>
								 <div class="form-actions">',
	'title_reply'			=>	'<legend>' . __( 'Leave a reply', 'the-bootstrap' ) . '</legend>',
	'title_reply_to'		=>	'<legend>' . __( 'Leave a reply to %s', 'the-bootstrap' ). '</legend>',
	'must_log_in'			=>	'<div class="must-log-in control-group controls">' . sprintf( __( 'You must be <a href="%s">logged in</a> to post a comment.', 'the-bootstrap' ), wp_login_url( apply_filters( 'the_permalink', get_permalink( get_the_ID() ) ) ) ) . '</div>',
	'logged_in_as'			=>	'<div class="logged-in-as control-group controls">' . sprintf( __( 'Logged in as <a href="%1$s">%2$s</a>. <a href="%3$s" title="Log out of this account">Log out?</a>', 'the-bootstrap' ), admin_url( 'profile.php' ), $user_identity, wp_logout_url( apply_filters( 'the_permalink', get_permalink( get_the_ID() ) ) ) ) . '</div>',
) );
}




#===========================================================
# Single comment templates
#===========================================================

/**
 * Based on `the_bootstrap_comment` in wp-content/themes/the-bootstrap/functions.php.
 */
function debiki_the_bootstrap_comment( $comment, $args, $depth ) {
	$GLOBALS['comment'] = $comment;
	switch ( $comment->comment_type ) :
		case 'pingback' :
		case 'trackback' :
			?>
			<li class="post pingback">
				<p class="row">
					<strong class="ping-label span1"><?php _e( 'Pingback:', 'the-bootstrap' ); ?></strong>
					<span class="span7"><?php comment_author_link(); edit_comment_link( __( 'Edit', 'the-bootstrap' ), '<span class="sep">&nbsp;</span><span class="edit-link label">', '</span>' ); ?></span>
				</p>
			<?php
			break;
		default :
			?>
			<li <?php comment_class('dw-t dw-depth-'.$depth); ?> id="li-comment-<?php comment_ID(); ?>">
				<a class="dw-z">[–]</a>
				<article id="comment-<?php comment_ID(); ?>" class="comment row dw-p">
					<div class="comment-author-avatar">
						<?php echo get_avatar( $comment, 70 ); ?>
					</div>
					<footer class="comment-meta dw-p-hd">
						<div class="comment-author vcard">
							<?php
								/* translators: 1: comment author, 2: date and time */
								printf( __( '%1$s <span class="says">said</span> on %2$s:', 'the-bootstrap' ),
									sprintf( '<span class="fn">%s</span>', get_comment_author_link() ),
									sprintf( '<a href="%1$s"><time pubdate datetime="%2$s">%3$s</time></a>',
										esc_url( get_comment_link( $comment->comment_ID ) ),
										get_comment_time( 'c' ),
										/* translators: 1: date, 2: time */
										sprintf( __( '%1$s at %2$s', 'the-bootstrap' ), get_comment_date(), get_comment_time() )
									)
								);
								edit_comment_link( __( 'Edit', 'the-bootstrap' ), '<span class="sep">&nbsp;</span><span class="edit-link label">', '</span>' ); ?>
						</div><!-- .comment-author .vcard -->

						<?php if ( ! $comment->comment_approved ) : ?>
						<div class="comment-awaiting-moderation alert alert-info"><em><?php _e( 'Your comment is awaiting moderation.', 'the-bootstrap' ); ?></em></div>
						<?php endif; ?>

					</footer><!-- .comment-meta -->

					<div class="comment-content dw-p-bd">
						<?php
						comment_text();
						?>
						<div class="dw-wp-reply-link" <?php echo debiki_reply_link_data($comment); ?> >
						<?php						
						comment_reply_link( array_merge( $args, array(
							'reply_text'	=>	__( 'Reply <span>&darr;</span>', 'the-bootstrap' ),
							'depth'			=>	$depth,
							'max_depth'		=>	$args['max_depth']
						) ) ); ?>
						</div>
					</div><!-- .comment-content -->

				</article><!-- #comment-<?php comment_ID(); ?> .comment -->
			<?php
			break;
	endswitch;
}
