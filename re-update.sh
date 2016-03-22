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

cd ../git_tmp

# minifies JS:

make_file_from_dir() {
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

make_file_from_dir 'vendor'
make_file_from_dir 'app'

cd ../TwitAudio

# copies everything in the current dir

cp -rf ../git_tmp/* .

# delete the temporary dir

rm -rf ../git_tmp

# returns the permissions to the current dir

chmod -R 777 .

# now, minify CSS

curl -X POST -s --data-urlencode 'input@assets/css/default.css' https://cssminifier.com/raw -o 'assets/css/default.css'