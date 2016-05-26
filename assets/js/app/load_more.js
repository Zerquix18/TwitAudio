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

	if( 'undefined' == typeof window.loadMore ) {
		return;
	}

	if( ! window.canScroll ) {
		return;
	}
	// set to false to avoid duplicates if the user
	// scrolls again and the request is not finished yet
	window.canScroll = false;

	window.loadMore.data.p++;
	
	$.ajax({
		type: "GET",
		cache: false,
		url: ajaxUrl + 'get/' + window.loadMore.toLoad,
		data: window.loadMore.data,
		dataType: 'json',
		complete: function() {
			window.canScroll = true;
		},
		error: function() {
			displayError(
						'There was an error while loading the content...' +
						'Please check your Internet connection.'
					);
		},
		success: function( result ) {
			if( ! result.success ) {
				displayError(result.response);
			}
			// success:
			$(window.loadMore.selector).append(result.response);
			updateDates();
			callOnLoadFunctions();
			
			if( ! result.load_more ) {
				delete window.loadMore;
			}
		}, // end success
	}); // end ajax
}); // end scroll function; don't delete the below line
