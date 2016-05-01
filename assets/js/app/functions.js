/**
* Functions file
*
**/

/**
* Checks if needle is in haystack
* in_array('lol', ['asd', 'lol']) = true
*
**/
function in_array( needle, haystack ) {
	return $.inArray( needle, haystack ) !== -1;
}
// displays a toast
function display_error( error, dissapear ) {
	var text  = '<i class="fa fa-close"></i>&nbsp;';
	text     += error;
	dissapear = dissapear || 5000;
	Materialize.toast(text, dissapear, 'rounded');
}
function display_info( info, dissapear ) {
	var text  = '<i class="fa fa-check"></i>&nbsp;';
	text     += info;
	dissapear = dissapear || 5000;
	Materialize.toast(text, dissapear, 'rounded');
}
// checks if str is JSON
function is_JSON( str ) {
	return (/^[\],:{}\s]*$/.test(str.replace(/\\["\\\/bfnrtu]/g, '@').replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']').replace(/(?:^|:|,)(?:\s*\[)+/g, '')));
}

function read_cookie( name ) {
    var value = document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)');
    return value ? value.pop() : '';
}

/** prompts the user if
*** it tries to leave without finishing **/

function unfinished_audio_helper( e ) {
	var confirmation_message = "You haven't finished uploading your audio. Are you sure you want to leave?";
	(e || window.event).returnValue = confirmation_message;
	return confirmation_message;
}
function unfinished_audio( action ) {

	this.unloaded = this.unloaded || false;

	if( 'start' === action && ! this.unloaded ) {
		this.unloaded = true;
		return window.addEventListener(
				'beforeunload',
				unfinished_audio_helper
			);
	} else if( 'stop' === action && this.unloaded ) {
		this.unloaded = false;
		return window.removeEventListener(
				'beforeunload',
				unfinished_audio_helper
			);
	}
	
	return void 0;
}