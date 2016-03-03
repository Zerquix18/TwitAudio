#!/bin/bash
# Reupdates everything from the git repo
#this must be temporary

#omg I just learned bash

# check if dir exists
if [ ! -d ../git_tmp ]; then
	mkdir '../git_tmp'
else
	exit 1 # if exists it is bcoz its already working
fi

#clones the repo in temporary folder

git clone https://zerquix18:958258b7999fd58990dcf9315637f4a6bc2c6169@github.com/superjd10/TwitAudio.git ../git_tmp

# permissions agen:

chmod -R 777 ../git_tmp

# copies everything in the current dir

cp -rf ../git_tmp/* .

# delete the temporary dir

rm -rf ../git_tmp

# returns the permissions to the current dir

chmod -R 777 .

# now, minify CSS and Javascript

curl -X POST -s --data-urlencode 'input@assets/css/default.css' https://cssminifier.com/raw -o 'assets/css/default.css'

curl -X POST -s --data-urlencode 'input@assets/js/default.js' https://javascript-minifier.com/raw -o 'assets/js/default.js'