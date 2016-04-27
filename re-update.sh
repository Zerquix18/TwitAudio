#!/bin/bash
# Reupdates everything from the git repo
#this must be temporary

#omg I just learned bash

# check if dir exists
if [ ! -d ../git_tmp ];
then
	mkdir '../git_tmp'
else
	exit 1 # if exists it is bcoz its already working
fi

#the current directory
home_dir=${PWD##*/}

#clones the repo in temporary folder
#if the argument 1 is sent, it will clone THAT BRANCH
#that's for the beta version
if [ -z "$1" ]; # no argument passed
then
	git clone https://zerquix18:958258b7999fd58990dcf9315637f4a6bc2c6169@github.com/superjd10/TwitAudio.git ../git_tmp
else # some branch was passed as a second argument
	result=$(git clone https://zerquix18:958258b7999fd58990dcf9315637f4a6bc2c6169@github.com/superjd10/TwitAudio.git -b "$1" ../git_tmp)
	if [ ! result ];
	then
		echo 'seems like branch does not exist'
		rm -rf ../git_tmp
		exit 1
	fi
fi

cd ../git_tmp

# minifies JS:

make_jsfile_from_dir() {
	key=$1
	i=0
	result=()
	file_='assets/js/scripts.json'
	array=($(jq --raw-output -c .$key[$i] $file_))
	while true;
	do
		array=($(jq --raw-output -c .$key[$i] $file_))
		if [ "$array" == "null" ]; then
			break
		fi
		result+=("$array")
		((i++))
	done
	# now $result are the files
	> "assets/js/$key.js" #make an empty file
	# append now $result[$i].js to $key.js
	for filename in "${result[@]}"
	do
		file_to_concatenate="assets/js/$key/$filename"
		cat "$file_to_concatenate" >> "assets/js/$key.js"
	done
	# now minify the entire file
	curl -X POST -s --data-urlencode "input@assets/js/$key.js" https://javascript-minifier.com/raw -o "assets/js/$key.js"
}

make_jsfile_from_dir 'vendor'
make_jsfile_from_dir 'app'

# minifies CSS:

make_cssfile_from_dir() {
	key=$1
	i=0
	result=()
	file_='assets/css/styles.json'
	array=($(jq --raw-output -c .$key[$i] $file_))
	while true;
	do
		array=($(jq --raw-output -c .$key[$i] $file_))
		if [ "$array" == "null" ]; then
			break
		fi
		result+=("$array")
		((i++))
	done
	# now $result are the files
	> "assets/css/$key.css" #make an empty file
	# append now $result[$i].css to $key.css
	for filename in "${result[@]}"
	do
		file_to_concatenate="assets/css/$key/$filename"
		cat "$file_to_concatenate" >> "assets/css/$key.css"
	done
	# now minify the entire file
	curl -X POST -s --data-urlencode "input@assets/css/$key.css" https://cssminifier.com/raw -o "assets/css/$key.css"
}

make_cssfile_from_dir 'vendor'
make_cssfile_from_dir 'app'
   
cd "../$home_dir"

# copies everything in the current dir

cp -rf ../git_tmp/* .

# delete the temporary dir

rm -rf ../git_tmp

# returns the permissions to the current dir

chmod -R 755 .

# install new dependencies or re-update the installed ones

composer update

# this fixes an error when you try to
# execute this file
# don't delete it
dos2unix `basename $0`