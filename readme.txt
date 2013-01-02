=== Debiki Wordpress Comments ===
Contributors:
Donate link:
Tags: comments
Requires at least: 3.4.2
Tested up to: 3.5
Stable tag: 3.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A hopefully better WordPress discussion system that contributes to fruitful
discusions. Main features: Comment ratings and a two dimensional layout.



== Description ==

See http://wordpress.debiki.com/



== Installation ==

1. Unzip `debiki-wordpress-comments.zip` in the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress



== Frequently Asked Questions ==

There are no frequently asked questions, as of right now.
You could ask questions here though:

http://wordpress.debiki.com/forum/



== Screenshots ==

Have a look at this page instead: http://wordpress.debiki.com/demo-1/
(There are no screenshots, as of right now.)



== Changelog ==

= v0.1 =
* Initial release (January 2013).



== Upgrade Notice ==

= v0.1 =
This is the initial release.



== Building ==

(Please read LICENSE.txt: there's no warranty.)

To compile, minify, and combine Javascript and LiveScript, you need to install
Node.js and Grunt.

Install Node.js:

$ git clone https://github.com/joyent/node.git
$ cd node/
$ ./configure 
$ make
$ make install

(perhaps with `sudo`)
(On my Ubuntu TurnKey Linux virtual machine, I also needed to `aptitude install g++`.)

Then install Grunt, and nodejs dependencies:

$ sudo npm install -g grunt  # see http://gruntjs.com/

$ npm install  # installs Node and Grunt dependencies

Now you can bundle JS and CSS files like so:

$ grunt



== Testing ==

1. Install PEAR and PHPUnit

Install PEAR:

Install version >= 1.9.4, because earlier version(s) are broken and won't work
with PHPUnit. See: http://stackoverflow.com/a/8952814/694469
Many Linux/Mac distros ship the broken version, it seems. (My did.)
If you need to uninstall the old broken version:
  $ sudo apt-get purge php5-pear  # with Ubuntu Linux.
                                  # Or simply `... purge php-pear`.

Installation instructions:
  http://pear.php.net/manual/en/installation.getting.php

This worked for me: (I'm using Ubuntu Linux)
  $ wget http://pear.php.net/go-pear.phar
  $ sudo php go-pear.phar


Configure PEAR: (I think `sudo` is needed, not sure)
$ sudo pear config-set auto_discover 1

Install PHPUnit: (I think `sudo` is needed, not sure)
$ sudo pear install pear.phpunit.de/PHPUnit

(In the future, will I use: pear install phpunit/DbUnit and
phpunit/PHPUnit_Selenium ?)

Configure wordpress-tests:
Edit wordpress-tests/unittests-config.php
and specify path to the WordPress codebase and database credentials.


2. Create a test database and a test user

$ mysql -h localhost -u root -p  # for example

CREATE USER wordpress_test@localhost IDENTIFIED BY 'wordpress_test';
CREATE DATABASE wordpress_test;
GRANT ALL ON wordpress_test.* TO wordpress_test@localhost;
FLUSH PRIVILEGES;

3.

Read
http://blog.doh.ms/2011/05/13/debugging-phpunit-tests-in-netbeans-with-xdebug/

4.

If you're using Netbeans, and debugging phpunit on a *remote machine*
(I do, I have my WordPress installation in a virtual machine),
then I think you need to:
  Add a Run Configuration to project `debiki-wordpress-comments`
  (File | Project Properties | Run Configuration)
  You could name it "Remote_phpunit_debugging"
  Click [Advanced]
  Select (o) Do Not Open Web Browser
  Map Server Path:  /var/www/wordpress/	  (for example)
  to Project Path:  /home/you/path/to/wp-content/parent/dir/


