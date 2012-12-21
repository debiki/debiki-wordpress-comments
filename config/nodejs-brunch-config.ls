exports.config =
  # See http://brunch.readthedocs.org/en/latest/config.html for documentation.

  paths:
    app: 'client'
    public: 'client'

  files:
    javascripts:
      defaultExtension: 'ls'
      joinTo:
        'combined-debiki-desktop.js':
          // ^client/debiki/debiki-action-delete.js
           | ^client/debiki/debiki-arrows-png.js
           | ^client/debiki/debiki-arrows-svg.js
           | ^client/debiki/debiki-form-anims.js
           | ^client/debiki/debiki-jquery-find.js
           | ^client/debiki/debiki.js
           | ^client/debiki/debiki-post-header.js
           | ^client/debiki/debiki-resize.js
           | ^client/debiki/debiki-scroll-into-view.js
           | ^client/debiki/debiki-show-and-highlight.js
           | ^client/debiki/debiki-util.js
           | ^client/debiki/debiki-util-browser.js
           | ^client/debiki/debiki-utterscroll-init-tips.js
           | ^client/debiki/debiki-utterscroll.js
           | ^client/vendor/jquery-scrollable.js
          //

        'combined-debiki-touch.js':
          // ^client/debiki/android-zoom-bug-workaround.js
           | ^client/debiki/debiki-arrows-png.js
           | ^client/debiki/debiki-arrows-svg.js
           | ^client/debiki/debiki-form-anims.js
           | ^client/debiki/debiki-jquery-find.js
           | ^client/debiki/debiki.js
           | ^client/debiki/debiki-post-header.js
           | ^client/debiki/debiki-resize.js
           | ^client/debiki/debiki-scroll-into-view.js
           | ^client/debiki/debiki-show-and-highlight.js
           | ^client/debiki/debiki-util.js
           | ^client/debiki/debiki-util-browser.js
          //

      order:
        after: ['client/debiki/debiki.js']

    stylesheets:
      defaultExtension: 'styl'
      joinTo:
        'combined-debiki.css':
          // ^client/debiki/
           | ^client/vendor/
          //
      order:
        before:
          * 'client/vendor/debiki.css'
          * 'client/vendor/jquery-ui/jquery-ui-1.8.16.custom.css'

  modules:
    definition: false
    wrapper: (path, data) -> """
      (function() {
        #{data}
      })();\n\n
      """

# vim: et ts=2 sw=2 list
