#!/bin/bash

set -u  # exit on unset variable
set -e  # exit on non-zero command exit code
set -o pipefail  # exit on false | true

play_dir=~/me-dev/debiki/all/debiki-app-play-2.1/

for f in `ls client/debiki/*.css` ; do
  cp $play_dir/$f client/debiki/
done

