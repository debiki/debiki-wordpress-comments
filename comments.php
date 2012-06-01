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

<div id="comments">
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

		<ol class="commentlist">
			<?php
				/* Loop through and list the comments. Tell wp_list_comments()
				 * to use twentyeleven_comment() to format the comments.
				 * If you want to overload this in a child theme then you can
				 * define twentyeleven_comment() and that will be used instead.
				 * See twentyeleven_comment() in twentyeleven/functions.php for more.
				 */
				wp_list_comments( array( 'callback' => 'twentyeleven_comment' ) );
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

	<?php comment_form(); ?>

</div>
