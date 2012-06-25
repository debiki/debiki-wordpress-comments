<?php
/**
 * This file is part of the Annotum theme for WordPress
 * Built on the Carrington theme framework <http://carringtontheme.com>
 *
 * Copyright 2008-2011 Crowd Favorite, Ltd. All rights reserved. <http://crowdfavorite.com>
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * Parts copyright (c) 2012 Kaj Magnus Lindberg (born 1979)
 * License: GPL v2 or later
 *
 *
 * I (KajMagnus) have edited the PHP code to output certain Debiki related CSS classes,
 * and some extra tags, and removed next and previous comment page links (which makes
 * no sense with Debiki's two dimensional layout).
 */


#===========================================================
# Comment section template
#===========================================================

/**
 * Based on: wp-content/themes/annotum-base/comments/comments-default.php
 *   (Annotum Base v. 1.0, WordPress v. 3.4)
 */

if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) { die(); }
if (CFCT_DEBUG) { cfct_banner(__FILE__); }

if (comments_open()) {
	if (!post_password_required() && have_comments() ) {
		?>
		<section id="replies">
			<div class="section-header clearfix">
				<h1 class="section-title"><?php comments_number('', __('1 Comment', 'anno'), __('% Comments', 'anno')); ?></h1>
				<?php post_comments_feed_link('Comments RSS'); ?>
			</div>

			<div id='comments' class='debiki dw-debate'>
			<div id="dw-t-1" class="dw-t dw-ar-t dw-depth-0 dw-hor dw-svg-gparnt">
			<div class='dw-p'></div>
			<div class='dw-t-vspace'></div>
			<ol class="reply-list dw-res ui-helper-clearfix">
			<?php
			comment_form();
			echo debiki_list_comments('debiki_cfct_threaded_comment');
			?>
			</ol>
			</div>
			</div>
		</section>

		<?php
	}
}



#===========================================================
# Single comment templates
#===========================================================


# Please don't indent method bodies. If you do that, then if you run a diff against
# one of the original source files, the diff output will be somewhat harder
# to interpret (even if you ignore whitespace).


# ===== Template selection function

/**
 * Based on: wp-content/themes/annotum-base/carrington-core/templates.php
 *
 * Currently not really needed with Debiki, since we currently ignore child theme
 * comment customization. (It'd be excessively unlikely that the child themes
 * outputs HTML and CSS that works with Debiki's comment system?).
 * Including this function regardless, because I'd like to replicate the related
 * Annotum Base code base fairly well, then it will (?) be easier to update
 * this plugin to work with newer version of Annotum Base (?).
 */
function debiki_cfct_threaded_comment($comment, $args = array(), $depth) {
	$GLOBALS['comment'] = $comment;
	$data = array(
		'args' => $args,
		'depth' => $depth,
	);
	debiki_annotum_base_comments_threaded($data);
}


# ===== The comment list item

/**
 * Based on: wp-content/themes/annotum-base/comments/threaded.php.
 */
function debiki_annotum_base_comments_threaded($data) {

global $comment;
// Why won't this append $depth??: $debiki_classes = 'dw-t dw-depth-'.$data->$depth;
extract($data);
$debiki_classes = 'dw-t dw-depth-'.$depth;
?>

<li class="li-comment <?php echo $debiki_classes ?>" id="li-comment-<?php comment_ID() ?>">
	<a class="dw-z">[–]</a>
	<?php /* <div class="div-comment dw-p" id="div-comment-<?php comment_ID(); ?>"> */?>
<?php
debiki_annotum_base_comment_default($data);
?>
	<?php # </div> ?>
<?php
// Dropped </li> is intentional: WordPress figures out where to place the </li> so it can nest comment lists.

}


# ===== The comment

/**
 * Based on: wp-content/themes/annotum-base/comment/comment-default.php
 */
function debiki_annotum_base_comment_default($data) {

global $comment, $post;
// Extract data passed in from threaded.php for comment reply link
extract($data);
?>
<article <?php comment_class('reply dw-p'); ?> id="comment-<?php comment_ID(); ?>">
	<?php debiki_annotum_base_comment_header(); ?>
	<div class="content dw-p-bd">
		<?php
		if ($comment->comment_approved == '0') {
			echo '<p><b>'.__('Your comment is awaiting moderation.', 'anno').'</b></p>';
		}
		comment_text();
		?>
	</div><!-- .content -->
	<div class="footer">
		<?php
			comment_reply_link(array_merge( $args, array('depth' => $depth, 'max_depth' => $args['max_depth'])), $comment, $post);
			edit_comment_link(__('Edit This', 'anno'), ' <span class="delimiter">&middot;</span> ', '');
		?>
	</div><!-- .footer -->
</article>

<?php
}


# ===== The comment header

/**
 * Based on: wp-content/themes/annotum-base/comment/comment-header.php
 */
function debiki_annotum_base_comment_header() {
?>

<?php global $comment; ?>
	<div class="header dw-p-hd">
		<?php echo get_avatar($comment, 40); ?>
		<h3 class="title"><?php comment_author_link(); ?></h3>
		<time class="published"><?php comment_date(); ?></time>
	</div>

<?php
}

?>
