/**
* Load more action
*
* @author Zerquix18
*
**/

window.can_scroll = true;

$(window).scroll( function() {
	if( (
		( $(window).scrollTop() + $(window).height() ) >
		( $(document).height() - 50 ) 
		) === false
	)
	return;

	if( null === document.getElementById("load_more") )
			return;

	if( ! window.can_scroll )
		return;

	// set to false to avoid duplicates if the user
	// scrolls again and the request is not finished yet
	window.can_scroll = false;

	var _load_more = $("#load_more"),
		load       = _load_more.data('load'),
		page       = _load_more.data('page'),
		extra      = _load_more.data('extra'),
		data       = {},
		to_load;

	// the first is the element to load
	// the second is a clone, so in case of file
	// we can put it again
	window.load_more = [ load, _load_more.clone() ];

	// The variables used here
	// are defined in the templates
	if( 'search' == load ) {
		/** defined in templates/search.phtml **/
		to_load = search;
		data.s  = sort;
		data.t  = type;
	} else if('audios' == load || 'favorites' == load )
		/** defined in templates/profile.phtml **/
		to_load = profile;
	else if('replies' == load ) {
		/** defined in templates/audio.phtml **/
		to_load = audio_id;
		data.reply_to = linked;
	}else
		return; // this must not happen

	data.p = page;
	data.q = to_load;

	$.ajax({
		type: "GET",
		cache: false,
		url: ajaxurl + 'get/' + load,
		data: data,
		beforeSend: function() {
			// a new one will be placed
			$("#load_more").remove();
		},
		error: function() {
			// place it again:
			$( '#' + window.load_more[0] ).append( window.load_more[1] );
			display_error('There was an error while loading the content... Please check your Internet connection.');
		},
		success: function( result ) {
			// if its JSON is because there was an error
			if( is_JSON(result) ) {
				result = JSON.parse(result);
				return display_error(result.response);
			}
			// if it's not a JSON is the HTML with the content
			// to place
			$( '#' + window.load_more[0] ).append( result );
		},
		complete: function() {
			delete window.load_more;
			window.can_scroll = true;
		}
	}); // end ajax
}); // end scroll function; don't delete the below line
