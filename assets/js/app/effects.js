/**
* Loads the effects and apply them
*
* @author Zerquix18
*
**/
window.effects = {

	all_effects_loaded: true,

	load_interval: false,

	loaded_effects: [],

	load_url_helper: -1,

	loaded_urls: [],

	load: function( audio_id ) {
		if( ! this.all_effects_loaded )
			return;
		if( null === this.load_interval ) {
			this.load_interval = setInterval(
					this.load,
					3000,
					audio_id
				);
			return; // wait the 3 seconds...
		}
		// proceed
		var params = {
			'id': audio_id
		};
		$.ajax({
			type:  "GET",
			cache: false,
			url:   ajaxurl + 'get/checkeffects',
			data:  params,
			success: function( result ) {

				result = JSON.parse(result);
				
				if( ! result.success ) {
					clearInterval( window.effects.load_interval );
					return display_error( result.response );
				}

				/* fun starts here */
				/** load all the audios with effects **/
				var loaded_effects = result.loaded_effects;

				for( var i = 0; i < loaded_effects.length; i++ ) {
					// 2 keys: file for the path,
					// and name for the effect name
					var name = loaded_effects[i].name,
						file = loaded_effects[i].file;

					if( in_array(name, window.effects.loaded_effects) )
						continue;
					else
						window.effects.loaded_effects.push(name);

					window.effects.loaded_urls.push( file );

					$(".choose_effect.effect_" + name)
						.attr('data-url', file);

					$("#effect_preview_" + name).jPlayer({
						ready: function(event) {
							window.effects.load_url_helper++;
							// ^ now when this motherfucking function
							// executes, it gets the right URL ↓
							$(this).jPlayer("setMedia", {
								mp3: window.effects.loaded_urls[
									window.effects.load_url_helper
								],
							});
						},

						play: function() {
							$(".jp-jplayer").not(this).jPlayer("pause");
						},

						cssSelectorAncestor : '#container_' + name,
						swfPath: "http://jplayer.org/latest/dist/jplayer",
						supplied: "mp3",
						wmode: "window",
						useStateClassSkin: true,
						autoBlur: false,
						smoothPlayBar: true,
						keyEnabled: true,
						remainingDuration: true,
						toggleDuration: true
	    				});
					// now, after it's loaded, show it.
					$("#effect_" + name + " > .loading").hide();
					$("#effect_" + name + " > .preview").show();
				}
				if( result.are_all_loaded ) {
					clearInterval(window.effects.load_interval);
					window.effects.all_effects_loaded  = true;
					window.effects.loaded_effects      = [];
					window.effects.load_url_helper     = -1;
				}
			}
		});
	},

	show_loading: function( effects ) {
		/**
		* We have now an array
		* each key is an object
		* with name and name_public
		**/
		for( var i = 0; i < effects.length; i++ ) {
			var effect_name        = effects[i].name;
			var effect_name_public = effects[i].name_public;
			var effect_selector    = 'effect_' + effects[i].name;

			$("#effect_none").clone() // make a copy
			.attr('id', effect_selector ) // change the ID
			// insert in the list of effects
			.appendTo('#effects_modal > .modal-content');

			// change the title
			$("#" + effect_selector + " h5").text( effect_name_public );

			// now the atts
			$("#" + effect_selector + ' .preview .jp-jplayer')
				.attr('id', 'effect_preview_' + effect_name);

			$("#" + effect_selector + ' .jp-audio')
				.attr('id', 'container_' + effect_name);

			// the choose button
			$("#" + effect_selector + ' .preview button')
				.attr('data-choose', effect_name)
				.removeClass('effect_none').addClass( effect_selector )
				.on('click', function() {

					if( undefined === $(this).data('url') )
						return false; // no urls loaded

					$.jPlayer.pause();
					$("#player_preview").jPlayer('setMedia', {
						'mp3' : $(this).data('url')
					});
					$("#effects_modal").closeModal();
					$("#audio_effect").val( $(this).data('choose') );

					if( 'original' == $(this).data('choose') )
						display_info('OK. Got it.');
					else
						display_info('Effect added!');

				});

			// now it is ready to be loaded by `this.load` :)

			$("#" + effect_selector).show();
			}
	},

	clean: function() {
		$(".effect_preview").not('#effect_none').remove(); // bye!
	}
	
};