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

/* Functions for dates */

/**
 * Returns the differences between 2 dates
 * 
 * @param  {Number} fromDate The date to calculate the differences.
 * @return {String}          
 */
function getDateDifferences( fromDate ) {
	// we play with milliseconds here
	var second   = 1000;
	var minute   = second   * 60;
	var hour     = minute   * 60;
	var day      = hour     * 24;
	fromDate     = fromDate * 1000;
	fromDate     = new Date(fromDate); 
	var toDate   = new Date();
	var diff     = toDate - fromDate;
	// now these are the differences: 
	var days     = Math.floor(diff / day);  
	var hours    = Math.floor(diff / hour);  
	var minutes  = Math.floor(diff / minute); 
	var seconds  = Math.floor(diff / second);

	if( seconds < 5 ) {
		return 'now';
	}
	if( seconds < 60 ) {
		return seconds + ' seconds';
	}
	if( 60 == seconds && seconds < 120 ) {
		return '1 minute';
	}
	if( minutes < 60 ) {
		return minutes + ' minutes';
	}
	if( 60 == minutes && minutes < 120 ) {
		return '1 hour';
	}
	if( hours < 24 ) {
		return hours + ' hours';
	}
	if( 1 == days ) {
		return 'yesterday'; // all my troubles seem so far away
	}
	if( days < 7) {
		// it is not yesterday
		return days + ' days';
	}
	if( 7 == days && days < 14 ) {
		return '1 week';
	}
	// cuz I'm too lazy to display full dates :)
	return Math.floor(days / 7) + ' weeks';
}
/**
 * Updates all the dates :O
 *
 */
function updateDates() {
	$('.datetime').each( function(i, obj) {
		// get the timestamp
		var timestamp = $(this).data('timestamp');
		// make it readable (i.e 1h, 2d, 74w)
		var date      = getDateDifferences( parseInt(timestamp) );
		// update it
		$(this).text(date);
	});
}
/** 
 * Calls the functions of window.onLoadFunctions which
 * is an array with functions to call after the body loaded.
 * I don't use $(document).ready because jQuery loads in the footer
 * and I don't use window.onload because it only supports 1 function.
 * 
 */
function callOnLoadFunctions() {
	for( var i = 0; i < window.onLoadFunctions.length; i++ ) {
		window.onLoadFunctions[i].call(null);
	}
}