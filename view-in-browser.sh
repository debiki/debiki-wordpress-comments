#!/bin/bash

# Opens in the browser an example blog page with comments,
# for all (?) themes that Debiki supports. Also opens each
# page with Debiki disabled, for comparison.

google-chrome 'http://192.168.122.83/2010/12/hello-world/?temp-theme=Responsive'
google-chrome 'http://192.168.122.83/2010/12/hello-world/?temp-theme=Responsive&debiki-comments-enabled=false'
google-chrome 'http://192.168.122.83/2010/12/hello-world/?temp-theme=Twenty+Ten'
google-chrome 'http://192.168.122.83/2010/12/hello-world/?temp-theme=Twenty+Ten&debiki-comments-enabled=false'
google-chrome 'http://192.168.122.83/2010/12/hello-world/?temp-theme=Twenty+Eleven'
google-chrome 'http://192.168.122.83/2010/12/hello-world/?temp-theme=Twenty+Eleven&debiki-comments-enabled=false'

