/**
 * Copyright (c) 2012 Kaj Magnus Lindberg (born 1979)
 * License: GPL v2 or later
 */

/**
 * This script:
 *  1. Unbinds any WordPress' reply link onclick handlers and instead
 *    adds jQuery animations that smoothly moves the reply form to where
 *    it is to be placed (when you've clicked a reply link, to reply
 *    to a specific comment).
 *  2. Submits comment ratings, on thumbs up/down click, and highlights the
 *    ratings.
 *  3. Highlights the user's ratings on page load (loads them
 *    from localStorage).
 *
 * This script is supposed to work with most / all themes actually,
 * not just Twenty Eleven. (Twenty Eleven files are fallbacked to,
 * when there are no theme specific files — and since there are no
 * other theme specific Javascript files, this Twenty Eleven Javascript
 * file is used, always).
 *
 * Find WordPress' corresponding script here:
 *   wp-includes/js/comment-reply.dev.js
 */

// ---------------------------------------------------------------------------
  jQuery(function($) {
// ---------------------------------------------------------------------------

/**
 * Animate the reply form; unbind WordPress Javascript.
 */
function moveReplyFormOnReplyClick() {
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
      $blogPostReplyFormPlaceholder = $('<div>Placeholder</div>')
      $blogPostReplyFormPlaceholder
          .width($replyForm.width())
          .height($replyForm.height())
          .css('padding', $replyForm.css('padding'))
          .css('margin', $replyForm.css('margin'))
          .css('visibility', 'hidden')
          .prependTo($replyFormOriginalParent);
    }

    $blogPostReplyFormPlaceholder.show();
  }

  function hideBlogPostReplyFormPlaceholder() {
    $blogPostReplyFormPlaceholder.hide();
  }

  // Move the reply form, if user clicks a comment's Reply link.
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
    var data = getCommentData($i);
    $replyForm.find('input[name=comment_parent]').val(data.parentCommentId);
    $replyForm.find('input[name=comment_post_ID]').val(data.blogPostId);

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
}


/**
 * Posts a comment rating, on thumbs up/down click.
 */
function submitRatingsOnThumbsClick() {
  $(document).on('click', '.dw-wp-vote-up .dw-wp-vote-link, '+
        '.dw-wp-vote-down .dw-wp-vote-link', function(event) {
    event.preventDefault();
    var $voteLink = $(this);
    var voteValue = $voteLink.closest('.dw-wp-vote-up').length ? '+1' : '-1';
    var $rateCommentForm = $('#dw-wp-rate-comment-form');
    var commentData = getCommentData($voteLink);
    $rateCommentForm.find('input[name=comment-id]').val(commentData.commentId);
    $rateCommentForm.find('input[name=vote-value]').val(voteValue);
    $.post($rateCommentForm.attr('action'), $rateCommentForm.serialize(),
        'html')
        .done(function(data) {
          highlightMyVote();
          if (!isLoggedIn()) rememberVoteInLocalStorage();
        })
        .fail(function() {
        })
        .always(function() {
        });

    function highlightMyVote(data) {
      // Remove all my-vote marks from current post,
      // then add new mark.
      var $myNewVote = $voteLink.parent();
      var $myOldVote =
          $voteLink.closest('.dw-wp-rate-links').find('.dw-wp-my-vote');
      var myOldVoteValue = '0';
      if ($myOldVote.is('.dw-wp-vote-up')) myOldVoteValue = '+1';
      if ($myOldVote.is('.dw-wp-vote-down')) myOldVoteValue = '-1';
      if (myOldVoteValue === voteValue)
        return;

      if ($myOldVote.length) {
        $myOldVote.removeClass('dw-wp-my-vote');
        incDecCommentVoteCount($myOldVote, -1);
      }

      $myNewVote.addClass('dw-wp-my-vote');
      incDecCommentVoteCount($myNewVote, 1);
    }

    function rememberVoteInLocalStorage() {
      var ratingsByPostAndComment = getMyOldRatings();
      var ratingsByComment = ratingsByPostAndComment[commentData.blogPostId];
      ratingsByComment[commentData.commentId] =
          { value: voteValue, datiStr: (new Date()).toISOString() };
      saveMyRatings(commentData.blogPostId, ratingsByComment);
    }
  });
}


/**
 * Looks up an unregistered user's earlier ratings in HTML5 local storage,
 * and highlights those ratings.
 */
function highlightAndIncMyRatings() {
  var ratingsByPostAndComment = getMyOldRatings();
  $.each(ratingsByPostAndComment, function(postId, ratingsByComment) {
    $.each(ratingsByComment, highlightAndIncRating);
  });

  function highlightAndIncRating(commentId, rating) {
    var $comment = $('#comment-'+ commentId);
    var $myVote = $();
    if (rating.value === '+1') {
      $myVote = $comment.find('.dw-wp-vote-up');
    }
    else if (rating.value === '-1') {
      $myVote = $comment.find('.dw-wp-vote-down');
    }
    else if (rating.value === '0') {
    }
    else {
      // COULD throw error?
    }

    // If we're being served an old cached version of the page,
    // it doesn't take $myVote into account. Then increment the
    // vote count manually here — otherwise the user might wonder if the
    // rating was suddenly lost, after voting and also reloading the page.
    if (debiki.wp.pageDatiStr < rating.datiStr) {
      incDecCommentVoteCount($myVote, +1);
    }

    $myVote.addClass('dw-wp-my-vote');
  }
}


/**
 * Returns an unregistered user's old ratings stored in HTML5 local storage.
 *
 * Access like so: returnValue[postId][commentId] -> rating
 */
function getMyOldRatings() {
  if (!localStorage || !JSON) return null; // IE 6 and 7
  var ratingsByPost = {};
  $('.dw-page').each(function() {
    var postId = $(this).data('dw_wp_post_id');
    var oldRatingsJson = localStorage.getItem(myRatingsItemName(postId));
    var oldRatings = JSON.parse(oldRatingsJson);
    ratingsByPost[postId] = $.extend({}, oldRatings);
  });
  return ratingsByPost;
}


function saveMyRatings(postId, ratings) {
  if (!localStorage || !JSON) return; // IE 6 and 7
  var ratingsJson = JSON.stringify(ratings);
  localStorage.setItem(myRatingsItemName(postId), ratingsJson);
}


function myRatingsItemName(postId) {
  // v0 means version 0. ((Some day in the future, I suppose we'll have to
  // migrate to version 1, and it feels safer to do that by copy-updating
  // from the v0 key to the v1 key, than by upgrading in place.))
  return 'dw-wp-v0-comment-ratings-on-post-'+ postId;
}


function isLoggedIn() {
  return debiki.wp.userId != 0;
}


/**
 * Finds comment id and post id.
 */
function getCommentData($elemInComment) {
    var $dataTag = $elemInComment.closest('.dw-p');
    return {
      commentId: $dataTag.attr('id').substr(8), // drop 'comment-'
      parentCommentId: $dataTag.data('dw_wp_comment_id'),
      blogPostId: $dataTag.data('dw_wp_post_id')
    }
}


function incDecCommentVoteCount($vote, change) {
  var $voteCount = $vote.find('.dw-wp-vote-count');
  var voteCountStr = $voteCount.text();
  var voteCount = parseInt(voteCountStr);
  $voteCount.text('' + (voteCount + change));
}


function createDateToISOStringIfAbsent() {
  // IE 6, 7, 8 has no toISOString.
  if (Date.prototype.toISOString)
    return;

  Date.prototype.toISOString = function () {
    function pad(n) {
      return n < 10 ? '0' + n : n;
    }
    return '"' + this.getUTCFullYear() + '-' +
        pad(this.getUTCMonth() + 1) + '-' +
        pad(this.getUTCDate())      + 'T' +
        pad(this.getUTCHours())     + ':' +
        pad(this.getUTCMinutes())   + ':' +
        pad(this.getUTCSeconds())   + 'Z"';
  };
}


function makeReplyTextareaResizable() {
  // If the textarea itself is made resizable, the resize handle is offset
  // incorrectly. See this bug report: http://bugs.jqueryui.com/ticket/4440
  var $textarea = $('#respond textarea').wrap('<div></div>');
  $textarea.parent().resizable({
    alsoResize: $textarea
  });
}


createDateToISOStringIfAbsent();
moveReplyFormOnReplyClick();
makeReplyTextareaResizable();
submitRatingsOnThumbsClick();


// For a registered logged in user, the html from the server already
// includes his/her ratings, highligted — if the cache settings
// recommended by WordPress Super Cache are used. However, these are not
// the default settings, which I would need to inform users of the plugin
// about.
if (!isLoggedIn())
  highlightAndIncMyRatings();


// ---------------------------------------------------------------------------
  });
// ---------------------------------------------------------------------------
