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

  $('.comment-reply-link').removeAttr('onclick').click(function(event) {
    var $i = $(this);

    $cancelCommentReplyLink.fadeIn();

    // Hide the 'Reply' link that the user just clicked, because it's
    // pointless to show a 'Reply' link just above the reply form.
    $lastReplyLinkClicked.show();
    $lastReplyLinkClicked = $i.closest('.reply');
    $lastReplyLinkClicked.hide();

    // Move the reply form.
    var $commentToReplyTo = $i.closest('.dw-p');
    $replyForm.hide().insertAfter($commentToReplyTo).slideDown('slow');

    event.preventDefault();
  });

  $cancelCommentReplyLink.removeAttr('onclick').click(function(event) {
    $cancelCommentReplyLink.hide();
    $lastReplyLinkClicked.fadeIn();

    $replyForm.slideUp(function() {
      $(this).prependTo($replyFormOriginalParent).fadeIn();
    });

    event.preventDefault();
  });

});
