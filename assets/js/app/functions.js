/**
* Functions file
*
**/

/**
 * Checks if needle is in haystack
 * @param  {string|number} needle
 * @param  {object}	       haystack
 * @return {boolean}         
**/
function inArray( needle, haystack ) {
	return $.inArray( needle, haystack ) !== -1;
}
/**
 * Displays a Materliaze Toast with an error message
 * @param  {String} error     The error message
 * @param  {Number} duration The duration of the message in milliseconds
 */
function displayError( error, duration ) {
	var text  = '<i class="fa fa-close"></i>&nbsp;';
	text     += error;
	duration  = duration || 5000;
	Materialize.toast(text, duration, 'rounded');
}
/**
 * Displays a Materliaze Toast with an info message
 * @param  {String} info     The info message
 * @param  {Number} duration The duration of the message in milliseconds
 */
function displayInfo( info, duration ) {
	var text  = '<i class="fa fa-check"></i>&nbsp;';
	text     += info;
	duration  = duration || 5000;
	Materialize.toast(text, duration, 'rounded');
}
/**
 * Checks if a string is a JSON string
 * @param  {String}  str The string to check
 * @return {Boolean}
 */
function isJson( str ) {
	return (/^[\],:{}\s]*$/.test(str.replace(/\\["\\\/bfnrtu]/g, '@').replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']').replace(/(?:^|:|,)(?:\s*\[)+/g, '')));
}
/**
 * Reads a cookie and returns its result
 * @param  {String} name The cookie to be read
 * @return {String}      The cookie content or an empty string
 *                       if it does not exist.
 */
function readCookie( name ) {
    var value = document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)');
    return value ? value.pop() : '';
}
/**
 * Helper for unfinishedAudio
 * @param  {The event} e
 * @return {String}
 */
function unfinishedAudioHelper( e ) {
	var confirmationMessage = 'You haven\'t finished uploading your audio. ' +
							'Are you sure you want to leave?';
	(e || window.event).returnValue = confirmationMessage;
	return confirmationMessage;
}
/**
 * Adds a 'beforeunload' event o removes it to prompt the user
 * if they try to leave without uploading the audio.
 * @param  {string} action Must be start|stop
 */
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