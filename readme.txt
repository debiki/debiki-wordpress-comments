=== Plugin Name ===
Contributors:
Donate link:
Tags: comments
Requires at least: 3.3.2
Tested up to: 3.3.2
Stable tag: 3.3.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Here is a short description of the plugin.  This should be no more than 150 characters.  No markup here.


== Description ==

This is the long description.  No limit, and you can use Markdown (as well as in the following sections).

For backwards compatibility, if this section is missing, the full length of the short description will be used, and
Markdown parsed.

A few notes about the sections above:

*   "Contributors" is a comma separated list of wp.org/wp-plugins.org usernames
*   "Tags" is a comma separated list of tags that apply to the plugin
*   "Requires at least" is the lowest version that the plugin will work on
*   "Tested up to" is the highest version that you've *successfully used to test the plugin*. Note that it might work on
higher versions... this is just the highest one you've verified.
*   Stable tag should indicate the Subversion "tag" of the latest stable version, or "trunk," if you use `/trunk/` for
stable.

    Note that the `readme.txt` of the stable tag is the one that is considered the defining one for the plugin, so
if the `/trunk/readme.txt` file says that the stable tag is `4.3`, then it is `/tags/4.3/readme.txt` that'll be used
for displaying information about the plugin.  In this situation, the only thing considered from the trunk `readme.txt`
is the stable tag pointer.  Thus, if you develop in trunk, you can update the trunk `readme.txt` to reflect changes in
your in-development version, without having that information incorrectly disclosed about the current stable version
that lacks those changes -- as long as the trunk's `readme.txt` points to the correct stable tag.

    If no stable tag is provided, it is assumed that trunk is stable, but you should specify "trunk" if that's where
you put the stable version, in order to eliminate any doubt.



== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `<?php do_action('plugin_name_hook'); ?>` in your templates


== Frequently Asked Questions ==

= A question that someone might have =

An answer to that question.

= What about foo bar? =

Answer to foo bar dilemma.



== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the directory of the stable readme.txt, so in this case, `/tags/4.3/screenshot-1.png` (or jpg, jpeg, gif)
2. This is the second screen shot



== Changelog ==

= 1.0 =
* A change since the previous version.
* Another change.

= 0.5 =
* List versions from most recent at top to oldest at bottom.


== Upgrade Notice ==

= 1.0 =
Upgrade notices describe the reason a user should upgrade.  No more than 300 characters.

= 0.5 =
This version fixes a security related bug.  Upgrade immediately.



== Building ==

To compile, minify, and combine Javascript and LiveScript, you need to install Node.js and Brunch.

Install Node.js:

$ git clone https://github.com/joyent/node.git
$ cd node/
$ ./configure 
$ make
$ make install

(perhaps with `sudo`)
(On my Ubuntu TurnKey Linux virtual machine, I also needed to `aptitude install g++`.)

Then install Brunch, and nodejs dependencies:

$ npm install brunch
$ npm install  # installs Node dependencies

Now you can bundle JS and CSS files like so:
$ brunch build -c config/nodejs-brunch-config.ls

And rebuild automatically on changes:
$ brunch watch -c config/nodejs-brunch-config.ls


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


