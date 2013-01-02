#!/bin/bash

# Unzips build/debiki-wordpress-comments.zip
# to wp-plugins/debiki-wordpress-comments-unzipped/
# so that I can verify that the .zip bundle works okay.

set -u  # exit on unset variable
set -e  # exit on non-zero command exit code
set -o pipefail  # exit on false | true

name=debiki-wordpress-comments
unzip_dir=$name-unzipped

# Verify that we're located in
#   wp-content/plugins/<name-of-debiki-wordpress-comments-dir>/.
# (this script exits on non-zero exit code).
ls ../../../wp-content/plugins >> /dev/null

# Recreate wp-content/plugins/debiki-wordpress-comments-unzipped/.
rm -fr ../$unzip_dir
mkdir ../$unzip_dir

# Unzip files.
unzip build/$name.zip -d ../$unzip_dir/
mv ../$unzip_dir/$name/* ../$unzip_dir/
rmdir ../$unzip_dir/$name/

# Rename the unzipped plugin, so it's possible to know which one
# is from the Git repo, and which one is the unzipped version.
sed 's/Plugin Name: Debiki for Wordpress/Plugin Name: Debiki for Wordpress UNZIPPED/' \
   ../$unzip_dir/debiki-wordpress.php > \
   ../$unzip_dir/debiki-wordpress.php-name-renamed
mv ../$unzip_dir/debiki-wordpress.php-name-renamed \
   ../$unzip_dir/debiki-wordpress.php

echo "Unzipped plugin to here: ../$unzip_dir/
and renamed it to: Debiki for Wordpress UNZIPPED"

