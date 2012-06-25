/**
 * Copyright (c) 2012 Kaj Magnus Lindberg (born 1979)
 * License: GPL v2 or later
 */

/**
 * Unbinds any WordPress' reply link onclick handlers and instead adds
 * jQuery animations that smoothly moves the reply form to where
 * it is to be placed (when you've clicked a reply link, to reply
 * to a specific comment).
 *
 * This is supposed to work with most / all themes actually,
 * not just Twenty Eleven. (Twenty Eleven files are fallbacked to,
 * when there are no theme specific files — and since there are no
 * other theme specific Javascript files, this Twenty Eleven Javascript
 * file is used, always).
 */

jQuery(function($) {

  var hideReplyLink = $('.dw-wp-hide-reply-link').length !== 0;
  var $replyForm = $('#respond');
  var $replyFormOriginalParent = $('#respond').parent();
  var $lastReplyLinkClicked = $();
  var $cancelCommentReplyLink = $('#cancel-comment-reply-link');

  // When the reply form is moved to elsewhere, these placeholders
  // are placed at the location of the reply form before it was moved.
  // Then all comments won't jump upwards/leftwards suddenly
  // (which they would have done, had the gap left by the reply form
  // not been filled by these placeholders).
  // (Their size exactly match the size of the reply form.)
  var $blogPostReplyFormPlaceholder = undefined;
  //var $commentReplyFormPlaceholder =
    //  $('<div id="dw-wp-comment-reply-form-placeholder"></div>');

  function showBlogPostReplyFormPlaceholder() {
    if (!$blogPostReplyFormPlaceholder) {
      $blogPostReplyFormPlaceholder = $replyForm.clone();
      $blogPostReplyFormPlaceholder.find('[id]').removeAttr('id');
      var $articleReplyList = $('.dw-depth-0 > .dw-res');
      $blogPostReplyFormPlaceholder
          .css('visibility', 'hidden')
          .prependTo($articleReplyList);
    }

    $blogPostReplyFormPlaceholder.show();
  }

  function hideBlogPostReplyFormPlaceholder() {
    $blogPostReplyFormPlaceholder.hide();
  }

  $('.comment-reply-link').removeAttr('onclick').click(function(event) {
    var $i = $(this);

    // Hide the 'Reply' link that the user just clicked, because it's
    // pointless to show a 'Reply' link just above the reply form.
    if (hideReplyLink) {
      $lastReplyLinkClicked.show();
      $lastReplyLinkClicked = $i.closest('.dw-wp-reply-link');
      if (!$lastReplyLinkClicked.length) $lastReplyLinkClicked = $i;
      $lastReplyLinkClicked.slideUp();
    }
    else {
      // Could disable it?
    }

    // Update reply form inputs, so one replies to the correct comment.
    var $dataTag = $i.closest('.dw-wp-reply-link');
    var parentCommentId = $dataTag.data('dw_wp_comment_id');
    var blogPostId = $dataTag.data('dw_wp_post_id');
    $replyForm.find('input[name=comment_parent]').val(parentCommentId);
    $replyForm.find('input[name=comment_post_ID]').val(blogPostId);

    // Move the reply form.
    showBlogPostReplyFormPlaceholder();
    var $commentToReplyTo = $i.closest('.dw-p');
    $replyForm.hide().insertAfter($commentToReplyTo)
        .slideDown(1100, function() {
      $cancelCommentReplyLink.fadeIn();
      // The $cancelCommentReplyLink is placed *above* the form, so it won't
      // appear unless:
      $replyForm.css('overflow', 'visible');
    });

    event.preventDefault();
  });

  $cancelCommentReplyLink.removeAttr('onclick').click(function(event) {
    $cancelCommentReplyLink.hide();
    if (hideReplyLink) {
      $lastReplyLinkClicked.fadeIn();
    }
    else {
      // Enable it?
    }

    $replyForm.slideUp(function() {
      hideBlogPostReplyFormPlaceholder();
      $(this).prependTo($replyFormOriginalParent).fadeIn();
    });

    event.preventDefault();
  });

});
