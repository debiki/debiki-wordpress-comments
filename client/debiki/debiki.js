/* Copyright (c) 2012 - 2013 Kaj Magnus Lindberg (born 1979).
 * Licensed under GNU GPL v2 or later, see LICENSE.txt and
 * http://www.gnu.org/licenses/gpl-2.0.html
 */


//========================================
   (function(){
//========================================
//----------------------------------------
   jQuery.noConflict()(function($){
//----------------------------------------

"use strict";

var d = { i: debiki.internal, u: debiki.v0.util };


d.i.rootPostId = $('.dw-depth-0');
d.i.rootPostId = d.i.rootPostId.length ?
    d.i.rootPostId.attr('id').substr(5) : undefined; // drops initial `dw-t-'

// If there's no SVG support, use PNG arrow images instead.
d.i.SVG = (function () {
  var svgSupported = Modernizr.inlinesvg;
  var svgEnabled = document.URL.indexOf('svg=false') === -1;
  var svgModuleAvailable = typeof d.i.makeSvgDrawer === 'function';
  if (svgSupported && svgEnabled && svgModuleAvailable)
    return d.i.makeSvgDrawer($);
  return d.i.makeFakeDrawer($);
})();


// Inits a post and its parent thread.
// Makes posts resizable, activates mouseenter/leave functionality,
// draws arrows to child threads, etc.
// Initing a thread is done in 4 steps. This function calls all those 4 steps.
// (The initialization is split into steps, so everything need not be done
// at once on page load.)
// Call on posts.
d.i.$initPostsThread = function() {
  $initPostsThreadStep1.apply(this);
  $initPostsThreadStep3.apply(this);
  $initPostsThreadStep4.apply(this);
};


function $initPostsThreadStep1() {
  // Open/close threads if the fold link is clicked.
  var $thread = $(this).closest('.dw-t');
  $thread.children('.dw-z').click(d.i.$threadToggleFolded);
};


function $initPostsThreadStep3() {
  // $initPostSvg takes rather long (190 ms on my 6 core 2.8 GHz AMD, for
  // 100 posts), and  need not be done until just before SVG is drawn.
  d.i.SVG.$initPostSvg.apply(this);
};


function $initPostsThreadStep4() {
  d.i.makeThreadResizableForPost(this);
};


// Inits a post, not its parent thread.
d.i.$initPost = function() {
  d.i.SVG.$initPostSvg.apply(this);
};


// Render the page step by step, to reduce page loading time. (When the first
// step is done, the user should conceive the page as mostly loaded.)

function renderPageEtc() {
  var $posts = $('.debiki .dw-p:not(.dw-p-ttl)');

  (d.u.workAroundAndroidZoomBug || function() {})($);

  // IE 6, 7 and 8 specific elems (e.g. upgrade-to-newer-browser info)
  // (Could do this on the server instead, that'd work also with Javascript
  // disabled. But people who know what javascript is and disable it,
  // probably don't use IE 6 and 7? So this'll be fine for now.)
  var $body =  $('body');
  if ($.browser.msie) {
    if ($.browser.version < '8') $body.addClass('dw-ua-lte-ie7');
    if ($.browser.version < '9') $body.addClass('dw-ua-lte-ie8');
  }

  if (!Modernizr.touch) {
    d.i.initUtterscrollAndTips();
  }

  var steps = [];

  steps.push(function() {
    $posts.each($initPostsThreadStep1);
    $('html').removeClass('dw-render-actions-pending');
  });

  steps.push(function() {
    $posts.each($initPostsThreadStep3);
  });

  // COULD fire login earlier; it's confusing that the 'Login' link
  // is visible for rather long, when you load a *huge* page.
  steps.push(function() {
    $posts.each($initPostsThreadStep4)
  });

  // Don't draw SVG until all html tags has been placed, or the SVG
  // arrows might be offset incorrectly.
  // Actually, drawing SVG takes long, so wait for a while,
  // don't do it on page load.
  steps.push(d.i.SVG.initRootDrawArrows);

  steps.push(d.i.scrollToUrlAnchorPost);

  steps.push(function() {
    debiki.scriptLoad.resolve();
  });

  function runNextStep() {
    steps[0]();
    steps.shift();
    if (steps.length > 0)
      setTimeout(runNextStep, 70);
  }

  setTimeout(runNextStep, 60);
};


if (!d.i.rootPostId) {
  // Skip most of the rendering step, since there is no root post.
  d.i.initUtterscrollAndTips();
  return;
}

renderPageEtc();


//----------------------------------------
   }); // end jQuery onload
//----------------------------------------
//========================================
   }()); // end Debiki module
//========================================


// vim: fdm=marker et ts=2 sw=2 tw=80 fo=tcqwn list
