// Config file for Javascript minification and concatenation.
// See http://gruntjs.com/

module.exports = function(grunt) {

  grunt.loadNpmTasks('grunt-contrib-mincss');
  grunt.loadNpmTasks('grunt-wrap');

  var debikiDesktopFiles = [
    // 'build/client/debiki/debiki-action-delete.js', // remove
    'build/client/debiki/debiki-arrows-png.js',
    //'build/client/debiki/debiki-arrows-svg.js',  // what? remove!?
    'build/client/debiki/debiki-form-anims.js',
    'build/client/debiki/debiki-jquery-find.js',
    'build/client/debiki/debiki-post-header.js',
    'build/client/debiki/debiki-resize.js',
    'build/client/debiki/debiki-scroll-into-view.js',
    'build/client/debiki/debiki-show-and-highlight.js',
    'build/client/debiki/debiki-util.js',
    'build/client/debiki/debiki-util-browser.js',
    'build/client/debiki/debiki-utterscroll-init-tips.js',
    'build/client/debiki/debiki-utterscroll.js',
    'build/client/vendor/jquery-scrollable.js',
    'build/client/debiki/debiki.js'];

  var debikiTouchFiles = [
    'build/client/debiki/android-zoom-bug-workaround.js',
    'build/client/debiki/debiki-arrows-png.js',
    //'build/client/debiki/debiki-arrows-svg.js',  // what? remove!?
    'build/client/debiki/debiki-form-anims.js',
    'build/client/debiki/debiki-jquery-find.js',
    'build/client/debiki/debiki-post-header.js',
    'build/client/debiki/debiki-resize.js',
    'build/client/debiki/debiki-scroll-into-view.js',
    'build/client/debiki/debiki-show-and-highlight.js',
    'build/client/debiki/debiki-util.js',
    'build/client/debiki/debiki-util-browser.js',
    'build/client/debiki/debiki.js'];

  grunt.initConfig({
    pkg: '<json:package.json>',
    banner: grunt.file.read('client/banner.js'),
    meta: {
      name: 'Debiki WordPress Comments',
      banner: '<%= banner %>'
    },
    wrap: {
      modules: {
        src: 'client/**/*.js',
        dest: 'build/',
        wrapper: ['(function() {\n', '\n})();']
      }
    },
    /* This results in malfunctioning CSS.
    mincss: {
      compress: {
        files: {
          "client/combined-debiki.css": [
            "client/vendor/jquery-ui/jquery-ui-1.8.16.custom.css",
            "client/debiki/debiki.css"]
        }
      }
    }, */
    concat: {
      'client/combined-debiki-desktop.js': debikiDesktopFiles,
      'client/combined-debiki-touch.js': debikiTouchFiles,
      'client/combined-debiki.css': [
        'client/banner.css',
        'client/vendor/jquery-ui/jquery-ui-1.8.16.custom.css',
        'client/debiki/debiki.css']
        //'client/combined-debiki.css']
    },
    min: {
      'client/combined-debiki-desktop.min.js': [
        '<banner>',
        'client/combined-debiki-desktop.js'
      ],
      'client/combined-debiki-touch.min.js': [
        '<banner>',
        'client/combined-debiki-touch.js'
      ]
    },
  });

  grunt.registerTask('default', 'mincss wrap concat min');

};

// vim: et ts=2 sw=2 list
