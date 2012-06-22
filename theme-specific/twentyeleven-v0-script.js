/**
 * Unbinds any WordPress' reply link onclick handlers and instead adds
 * jQuery animations that smoothly moves the reply form to where
 * it is to be placed (when you've clicked a reply link, to reply
 * to a specific comment).
 *
 * This is supposed to work with most / all themes actually,
 * not just Twenty Eleven.
 */

jQuery(function($) {

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
    $lastReplyLinkClicked.show();
    $lastReplyLinkClicked = $i.closest('.reply');
    $lastReplyLinkClicked.slideUp();

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
    $lastReplyLinkClicked.fadeIn();

    $replyForm.slideUp(function() {
      hideBlogPostReplyFormPlaceholder();
      $(this).prependTo($replyFormOriginalParent).fadeIn();
    });

    event.preventDefault();
  });

});
