/**
* Load more action
*
* @author Zerquix18
*
**/

window.canScroll = true;

$(window).scroll( function() {
	if( (
		( $(window).scrollTop() + $(window).height() ) >
		( $(document).height() - 50 ) 
		) === false
	) {
		return;
	}

	if( null === document.getElementById("load_more") ) {
		return;
	}

	if( ! window.canScroll ) {
		return;
	}
	window.canScroll = false;
	// set to false to avoid duplicates if the user
	// scrolls again and the request is not finished yet

	var toLoad;
	var params    = {};
	var _loadMore = $("#load_more");
	var load      = _loadMore.data('load');
	var page      = _loadMore.data('page');
	var extra     = _loadMore.data('extra');

	// the first is the element to load
	// the second is a clone, so in case of file
	// we can put it again
	window.loadMore = [ load, _loadMore.clone() ];

	// The variables used here
	// are defined in the templates
	if( 'search' == load ) {
		/** defined in templates/search.phtml **/
		toLoad   = search;
		params.s = sort;
		params.t = type;
	} else if('audios' == load || 'favorites' == load ) {
		/** defined in templates/profile.phtml **/
		toLoad = profile;
	} else if('replies' == load ) {
		/** defined in templates/audio.phtml **/
		toLoad = audioId;
		params.reply_to = linked;
	}else {
		return; // this must not happen
	}

	params.p = page;
	params.q = toLoad;

	$.ajax({
		type: "GET",
		cache: false,
		url: ajaxUrl + 'get/' + load,
		data: params,
		beforeSend: function() {
			// a new one will be placed
			$("#load_more").remove();
		},
		error: function() {
			// place it again:
			$( '#' + window.loadMore[0] ).append( window.loadMore[1] );
			displayError(
						'There was an error while loading the content...' +
						'Please check your Internet connection.'
					);
		},
		success: function( result ) {
			// if its JSON is because there was an error
			if( isJson(result) && '' !== result ) {
				result = JSON.parse(result);
				return displayError(result.response);
			}
			// if it's not a JSON is the HTML with the content
			// to place
			$( '#' + window.loadMore[0] ).append( result );
		},
		complete: function() {
			delete window.loadMore;
			window.canScroll = true;
		}
	}); // end ajax
}); // end scroll function; don't delete the below line
