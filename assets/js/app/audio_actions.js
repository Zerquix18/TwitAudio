/**
 * Actions made to an audio
 *
 * favorite
 * delete
 * play
 *
 * @author Zerquix18
 * @copyright 2016 Luis A. Martínez
**/

/**
 * Favorites an audio.
 */
$(document).on('click', '.audio-action-favorite', function(e) {
	var id = $(this).data('id');
	var lastFavoriteId = $(this);
	var params = {
			id: id,
			action: $(this).hasClass('favorited') ? // faved already?
				'unfav' : 'fav'
		};

	$.ajax({
		type: "POST",
		cache: false,
		url: ajaxUrl + 'post/favorite',
		data: params,
		beforeSend: function() {
			// FAKE AJAX
			var attribute = lastFavoriteId.find('span'),
				count     = parseInt( attribute.text() );

			if( lastFavoriteId.hasClass('is-favorited') ) { // already faved?
				lastFavoriteId.removeClass('is-favorited');
				attribute.text( String(count - 1) );
			}else{
				lastFavoriteId.addClass('is-favorited');
				attribute.text( String(count + 1) );
			}
		},
		error: function() {
			// get everything back
			var attribute = lastFavoriteId.find('span');
			var count     = parseInt( attribute.text() );

			if( lastFavoriteId.hasClass('is-favorited') ) {
				lastFavoriteId.removeClass('is-favorited');
				attribute.text( String(count - 1) );
				displayError(
					'There was a problem while favoriting the audio...'
				);
			}else{
				lastFavoriteId.addClass('is-favorited');
				attribute.text( String(count + 1) );
				displayError(
					'There was a problem while unfavoriting the audio...'
				);
			}
		},
		success: function(result) {
			result = JSON.parse(result);

			if( ! result.success ) {

				var attribute = lastFavoriteId.find('span');
				var count     = parseInt( attribute.text() );

				if( lastFavoriteId.hasClass('is-favorited') ) {
					lastFavoriteId.removeClass('is-favorited');
					attribute.text( String(c - 1) );
				}else{
					attribute.text( String(c + 1) );
					lastFavoriteId.addClass('is-favorited');
				}

				return displayError(result.response);
			}

			lastFavoriteId.find('span').html(result.count);
		}
	});
});

/**
 * Registers a play
 */

window.playedAudios = [];

$(document).on('click', '.audio-action-play', function(e) {
	var id = $(this).data('id');
	if( inArray( id, window.playedAudios ) ) {
		// don't register it again
		return;
	}

	window.playedAudios.push(id);

	$.ajax({
		type: "POST",
		cache: false,
		url: ajaxUrl + 'post/play',
		data: {id: id},
		success: function( result ) {
			result = JSON.parse(result);
			if( ! result.success ) {
				// we should not throw an error
				// because we could not count a play
				return;
			}
			// add the play
			$( '.audio-plays-' + // the last one registered ↓
				window.playedAudios[ window.playedAudios.length - 1 ]
			).find('span').html( result.count );
		}
	});

});

/** delete **/

$(document).on('click', '.audio-action-delete', function(e) {

	if( true !== confirm('Are you sure you want to delete this audio?') )
		return false;

	var id = $(this).data('id');
	$.ajax({
		type: "POST",
		cache: false,
		url: ajaxUrl + 'post/delete',
		data: {id: id},
		error: function() {
			displayError('There was an error while deleting your audio');
		},
		success: function(result) {
			result = JSON.parse(result);
			if( ! result.success ) {
				return displayError(result.response);
			}

			// redirect to home if the deleted audio
			// was in the audio page AND
			// was not a reply
			// audioId is defined in templates/audio.phtml
			if( typeof audioId !== 'undefined' &&
				result.response == audioId
				) {
				return window.location.replace('/');
			}

			$(".audio-" + result.id ).fadeOut(1000, function() {
				$(this).remove();
			});

		}
	});
});