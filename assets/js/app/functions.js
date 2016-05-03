/**
* Functions file
*
**/

/**
* Checks if needle is in haystack
* inArray('lol', ['asd', 'lol']) = true
*
**/
function inArray( needle, haystack ) {
	return $.inArray( needle, haystack ) !== -1;
}
// displays a Materialize toast
function displayError( error, dissapear ) {
	var text  = '<i class="fa fa-close"></i>&nbsp;';
	text     += error;
	dissapear = dissapear || 5000;
	Materialize.toast(text, dissapear, 'rounded');
}
function displayInfo( info, dissapear ) {
	var text  = '<i class="fa fa-check"></i>&nbsp;';
	text     += info;
	dissapear = dissapear || 5000;
	Materialize.toast(text, dissapear, 'rounded');
}
// checks if str is JSON
function isJson( str ) {
	return (/^[\],:{}\s]*$/.test(str.replace(/\\["\\\/bfnrtu]/g, '@').replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']').replace(/(?:^|:|,)(?:\s*\[)+/g, '')));
}

function readCookie( name ) {
    var value = document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)');
    return value ? value.pop() : '';
}

/** prompts the user if
*** it tries to leave without finishing **/

function unfinishedAudioHelper( e ) {
	var confirmationMessage = 'You haven\'t finished uploading your audio. ' +
							'Are you sure you want to leave?';
	(e || window.event).returnValue = confirmationMessage;
	return confirmationMessage;
}
function unfinishedAudio( action ) {

	this.unloaded = this.unloaded || false;

	if( 'start' === action && ! this.unloaded ) {
		this.unloaded = true;
		return window.addEventListener(
				'beforeunload',
				unfinishedAudioHelper
			);
	} else if( 'stop' === action && this.unloaded ) {
		this.unloaded = false;
		return window.removeEventListener(
				'beforeunload',
				unfinishedAudioHelper
			);
	}
	
	return void 0;
}