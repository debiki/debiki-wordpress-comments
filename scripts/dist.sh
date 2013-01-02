#!/bin/bash

# This is a Linux Bash script that builds a downnloadable .zip file
# with the Debiki WordPress Comments plugin. It can be unzipped and
# installed (that is, unzipped in the plugins/ directory) by people
# who don't know about Git or how to install Node.js.
#
# The generated .zip is placed here:  build/debiki-wordpress-comments.zip

set -u  # exit on unset variable
set -e  # exit on non-zero command exit code
set -o pipefail  # exit on false | true

build=build
name=debiki-wordpress-comments
temp=$build/debiki-wordpress-comments

rm -fr $temp
rm $build/$name.zip

mkdir $temp
mkdir $temp/client
mkdir $temp/docs
mkdir $temp/theme-specific

echo "Bundling and minifying Javascript..."
echo "(If this fails, please read the 'Building' section in readme.txt.)"

grunt


cp client/combined-* $temp/client/
cp -a client/img $temp/client/
cp *.php $temp/
cp docs/license-* $temp/docs/
cp LICENSE.txt $temp/
cp readme.txt $temp/
cp -a theme-specific $temp/

pushd .
cd $build/
echo "Creating: `pwd`/$name.zip"
zip -r $name.zip $name
popd

echo "Done. Find a Zip archive with Debiki WordPress Comments here:"
echo "  $build/$name.zip"

