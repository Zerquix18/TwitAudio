/**
* Loads the effects and apply them
*
* @author Zerquix18
*
**/
window.effects = {

	allEffectsLoaded: true,

	loadInterval: false,

	loadedEffects: [],

	loadUrlHelper: -1,

	loadedUrls: [],

	load: function( audioId ) {
		if( ! this.allEffectsLoaded ) {
			return;
		}
		if( null === this.loadInterval ) {
			this.loadInterval = setInterval(
					this.load,
					3000,
					audioId
				);
			return; // wait the 3 seconds...
		}

		var params = {'id': audioId};
		$.ajax({
			type:  "GET",
			cache: false,
			url:   ajaxUrl + 'get/checkeffects',
			data:  params,
			success: function( result ) {

				result = JSON.parse(result);
				
				if( ! result.success ) {
					clearInterval( window.effects.loadInterval );
					return displayError(result.response);
				}

				/* fun starts here */
				/** load all the audios with effects **/
				var loadedEffects = result.loaded_effects;
				var areAllLoaded  = result.are_all_loaded;

				for( var i = 0; i < loadedEffects.length; i++ ) {
					// 2 keys: file for the path,
					// and name for the effect name
					var name = loadedEffects[i].name;
					var file = loadedEffects[i].file;

					if( inArray(name, window.effects.loadedEffects) ) {
						continue;
					} else {
						window.effects.loadedEffects.push(name);
					}

					window.effects.loadedUrls.push(file);

					$(".choose_effect.effect_" + name)
						.attr('data-url', file);

					$("#effect_preview_" + name).jPlayer({
						ready: function(event) {
							window.effects.loadUrlHelper++;
							// ^ now when this motherfucking function
							// executes, it gets the right URL ↓
							$(this).jPlayer("setMedia", {
								mp3: window.effects.loadedUrls[
									window.effects.loadUrlHelper
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
				if( areAllLoaded ) {
					clearInterval(window.effects.loadInterval);
					window.effects.allEffectsLoaded  = true;
					window.effects.loadedEffects     = [];
					window.effects.loadUrlHelper     = -1;
				}
			}
		});
	},

	showLoading: function( effects ) {
		/**
		* We have now an array
		* each key is an object
		* with name and name_public
		**/
		for( var i = 0; i < effects.length; i++ ) {
			var effectName        = effects[i].name;
			var effectNamePublic  = effects[i].name_public;
			var effectSelector    = 'effect_' + effects[i].name;

			$("#effect_none")
				.clone()
				.attr('id', effectSelector)
				.appendTo('#effects_modal > .modal-content');

			// change the title
			$("#" + effectSelector + " h5")
				.text(effectNamePublic);

			// now the atts
			$("#" + effectSelector + ' .preview .jp-jplayer')
				.attr('id', 'effect_preview_' + effectName);

			$("#" + effectSelector + ' .jp-audio')
				.attr('id', 'container_' + effectName);

			// the choose button
			$("#" + effectSelector + ' .preview button')
				.attr('data-choose', effectName)
				.removeClass('effect_none').addClass(effectSelector)
				.on('click', function() {

					if( undefined === $(this).data('url') ) {
						// no urls loaded
						return false;
					}

					$.jPlayer.pause();
					$("#player_preview").jPlayer('setMedia', {
						'mp3' : $(this).data('url')
					});
					$("#effects_modal").closeModal();
					$("#audio_effect").val( $(this).data('choose') );

					if( 'original' == $(this).data('choose') ) {
						displayInfo('OK. Got it.');
					} else {
						displayInfo('Effect added!');
					}

				});

				// now it is ready to be loaded by `this.load` :)

				$("#" + effectSelector).show();
			}
	},

	clean: function() {
		// arrivederchi!
		$(".effect_preview").not('#effect_none').remove();
	}
	
};