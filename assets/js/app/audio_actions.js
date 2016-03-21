/**
* Actions made to an audio
*
* favorite
* delete
* play
*
**/

/** favorite **/
// called 'laic' (like) cause in the start they were likes

$(document).on('click', '.laic', function(e) {
	var id = $(this).data('id'),
		last_favorite_id = $(this),
		params = {
			id: id,
			action: $(this).hasClass('favorited') ? // faved already?
				'unfav' : 'fav'
		};

	$.ajax({
		type: "POST",
		cache: false,
		url: ajaxurl + 'post/favorite',
		data: params,
		beforeSend: function() {
			// FAKE AJAX
			var attribute = last_favorite_id.find('span'),
				count     = parseInt( attribute.text() );

			if( last_favorite_id.hasClass('favorited') ) { // already faved?
				last_favorite_id.removeClass('favorited');
				attribute.text( String(count - 1) );
			}else{
				last_favorite_id.addClass('favorited');
				attribute.text( String(count + 1) );
			}
		},
		error: function() {
			// get everything back
			var attribute = last_favorite_id.find('span'),
				count     = parseInt( attribute.text() );

			if( last_favorite_id.hasClass('favorited') ) {
				last_favorite_id.removeClass('favorited');
				attribute.text( String(count - 1) );
				display_error(
					'There was a problem while favoriting the audio...'
				);
			}else{
				last_favorite_id.addClass('favorited');
				attribute.text( String(count + 1) ); // increase 1
				display_error(
					'There was a problem while unfavoriting the audio...'
				);
			}
		},
		success: function(result) {
			result = JSON.parse(result);

			if( ! result.success ) {

				var attribute = last_favorite_id.find('span'),
					count     = parseInt( attribute.text() );

				if( last_favorite_id.hasClass('favorited') ) {
					last_favorite_id.removeClass('favorited');
					attribute.text( String(c - 1) );
				}else{
					attribute.text( String(c + 1) );
					last_favorite_id.addClass('favorited');
				}

				return display_error(result.response);
			}

			last_favorite_id.find('span').html(result.count);
		}
	});
});

/** play **/

window.played_audios = [];

$(document).on('click', '.plei', function(e) {
	var id = $(this).data('id');
	if( in_array( id, window.played_audios ) )
		return; // don't register it again

	window.played_audios.push(id);

	$.ajax({
		type: "POST",
		cache: false,
		url: ajaxurl + 'post/play',
		data: {id: id},
		success: function( result ) {
			result = JSON.parse(result);
			if( ! result.success )
				return; // no error must be given
			// add the play
			$( '#plays_' + // the last one registered ↓
				window.played_audios[ window.played_audios.length - 1 ]
			).find('span').html( result.count );
		}
	});

});

/** delete **/

$(document).on('click', '.delit', function(e) {

	if( true !== confirm('Are you sure you want to delete this audio?') )
		return false;

	var id = $(this).data('id');
	$.ajax({
		type: "POST",
		cache: false,
		url: ajaxurl + 'post/delete',
		data: {id: id},
		error: function() {
			display_error('There was an error while deleting your audio');
		},
		success: function(result) {
			result = JSON.parse(result);
			if( ! result.success )
				return display_error(result.response);

			// redirect to home if the deleted audio
			// was in the audio page AND
			// was not a reply
			// audio_id is defined in templates/audio.phtml
			if( typeof audio_id !== 'undefined' && result.response == audio_id )
				return window.location.replace('/');

			$(".audio_" + result.id ).fadeOut(1000, function() {
				$(this).remove();
			});

		}
	});
});