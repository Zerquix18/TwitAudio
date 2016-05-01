/**
* Functions and listeners for forms
*
* @author Zerquix18
*
**/

/************************* UPLOAD *************************/

/**
* Uploads an audio
* If options.is_voice = true then
* it will upload the binary of the recorder
* if not, it will upload the 'up_file' input
*
**/
window.upload_audio = function( options ) {
		var is_voice = options.is_voice || false;
		$("#record_form").hide();
		$("#post").hide();
		window.progressive_text.start(
			'#whatsloading',
			[
				'Uploading...',
				'Getting prepared...',
				'Fighting with the gravity...',
				'Uplading your audio at 300,001km/s',
				'Just doing it...',
				'Pushing it up...',
				'This is a really long audio...',
				"I'll have to use a lift to upload this",
				'I think your audio is a little fat',
				'Uploading...'
			],
			3
		);
		$("#loading").show();
		var _ajax_form = {
			beforeSend: function() {
				$("#player_cut").jPlayer("destroy");
				window.effects.clean();
			},
			error: function( xhr ) {
				display_error(
					'There was an error while uploading your audio. ' + 
					'Please check your Internet connection');
				$("#loading").hide();
				$("#up_progress").width(0);
				$("#post").show();
				window.progressive_text.stop('#whatsloading');
				$("#up_form").trigger('reset');
			},
			uploadProgress: function( event, position, total, percent ) {
				$("#up_progress").animate({
					width: percent + '%'
				});
			},
			complete: function( xhr ) {
				window.progressive_text.stop('#whatsloading');
				$("#up_form").trigger('reset');
				$("#up_progress").width("100%");
				var result = JSON.parse(xhr.responseText);
				if( false === result.success ) {
					if( typeof result.tmp_url == 'undefined' ) {
						$("#loading").hide();
						$("#up_progress").width(0);
						$("#post").show();
						display_error( result.response );
						return;
					}
					unfinished_audio('start');
					window.tmp_url = result.tmp_url;
					$("#player_cut").jPlayer({
						ready: function(event) {
							$(this).jPlayer("setMedia", {
							mp3: window.tmp_url,
						});
						},
						cssSelectorAncestor: '#cut_container',
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
					$("#loading").hide();
					$("#up_progress").width(0);
					$("#cut_form").show();
					$("#audio_id").val( result.id );
					return;
				}
				$(".original").data('url', result.tmp_url);

				unfinished_audio('start');

				window.effects.load( result.id );
				window.effects.show_loading( result.effects );

				window.prepare_post_form(result.id, result.tmp_url);
			}
		};
		if( is_voice && window.record.recorder ) {
			/**
			* If it's voice then we'll upload the
			* base64 as 'bin'
			**/
			window.record.recorder.exportMP3( function(blob) {
				var reader = new FileReader();
				reader.onload = function(event) {
					var file_reader      = {};
					file_reader.is_voice = '1';
					file_reader.bin      =  event.target.result;
					_ajax_form.data      = file_reader;
					$("#up_form").ajaxSubmit(_ajax_form);
				};
				reader.readAsDataURL(blob);
			});
			return;
		}
		_ajax_form.data = {is_voice: '0'};
		$("#up_form").ajaxSubmit(_ajax_form);
};

/*
* Will execute when the user clicks on the upload icon
*/

$("#upload").on('click', function() {
	$("#up_file").trigger('click');
	$(this).blur();
});

/**
* Will execute when the user tries to upload an audio
**/

$("#up_file").on('change', function() {

	var format    = $(this).val().split('.');
	var file_size = this.files[0].size / 1024 / 1024;

	format = format[ format.length - 1 ];
	format = format.toLowerCase();

	if( ! in_array(format, ['mp3', 'ogg', 'aac', 'wav', 'm4a'] ) ) {
		return display_error('Format not allowed');
	}

	/*
	* upload_file_limit is defined in templates/footer.phtml
	*/ 
	if( file_size > upload_file_limit ) {
		return display_error(
			'The file size is greater than your current ' +
			'limit \'s, ' + upload_file_limit + ' mb');
	}

	window.upload_audio( {is_voice: false } );
});

/************************* CUT *************************/

/*
* Will execute when the user submits
* The cut form
**/

$("#cut_form").ajaxForm({
	beforeSend: function() {
		$("#player_preview").jPlayer('destroy');
		$("#cut_form").hide();
		$("#up_progress").removeClass('determinate')
				     .addClass('indeterminate');
		window.progressive_text.start(
			'#whatsloading',
			[
				'Cutting...',
				'Getting prepared',
				'Looking for my scissors...',
				'Nice audio by the way...',
				'Have you considered to take singing classes?',
				'This audio is so deep I see Adele rolling on it',
				'This is taking too long...',
				'Cutting...'
			],
			5
		);
		$("#loading").show();
		$.jPlayer.pause();
		window.effects.clean();
	},
	error: function() {
		progressive_text.stop('#whatsloading');
		display_error(
			'There was a problem while cutting your audio. Please check your Internet connection',
			10000
		);
		// get everything back
		$("#loading").hide();
		$("#up_progress").removeClass('indeterminate')
				.addClass('determinate');
		$("#cut_form").show();
	},
	complete: function(xhr) {
		progressive_text.stop('#whatsloading');
		$("#up_progress").removeClass('indeterminate')
				.addClass('determinate');
		var result = JSON.parse(xhr.responseText);
		if( ! result.success ) {
			display_error(result.response);
			$("#loading").hide();
			$("#cut_form").show();
			return;
		}
		$("#cut_form").trigger('reset');
		$(".original").data('url', result.tmp_url);

		effects.load( result.id );
		effects.show_loading( result.effects );

		window.prepare_post_form( result.id, result.tmp_url );
	}
});

/*
* Will validate the #cut_form inputs
*/

$("#end, #start").on('keyup', function() {

	var numbers,
		diff,
		btn   = $("#cut_button"),
		start = $("#start").val(),
		end   = $("#end").val(),
		is_numeric = function( value ) {
			return /^[0-9]{0,3}$/.test(value);
		};

	if( ! is_numeric( start ) ) {

		if( ! /^([0-9]{1,2}):([0-9]{1,2})$/.test( start ) )
			return btn.attr('disabled', 'disabled');

		numbers = start.split(':');
		start = ( parseInt(numbers[0]) * 60 ) + parseInt(numbers[1]);
	}else {
		start = parseInt( start );
	}

	if( ! is_numeric( end ) ) {

		if( ! /^([0-9]{1,2}):([0-9]{1,2})$/.test( end ) )
			return btn.attr('disabled', 'disabled');

		numbers = end.split(':');
		end = ( parseInt(numbers[0]) * 60 ) + parseInt(numbers[1]);
	}else {
		end = parseInt( end );
	}

	diff = end-start;
	/* max_duration is declared in templates/footer.phtml */
	if( (start >= end) || diff > max_duration || diff < 1 ) {
		return btn.attr('disabled', 'disabled');
	}

	return btn.removeAttr('disabled');
});

$("#cut_cancel, #post_cancel").on('click', function() {
	if( true !== confirm('Are you sure?') ) {
		return false;
	}
	$("#cut_form, #post_form").hide();
	$("#post").show();
	$.jPlayer.pause();
	unfinished_audio('stop');
});

/************************* POST *************************/

window.prepare_post_form = function( id, tmp_url ) {

	$("#a_id").val(id);

	window.tmp_post_preview = tmp_url;

	$("#player_preview").jPlayer({
		ready: function(event) {
				$(this).jPlayer("setMedia", {
				mp3: window.tmp_post_preview,
			});
		},
		cssSelectorAncestor: '#preview_container',
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

	$("#cutting, #loading").hide();
	$("#post_form").show();
	$("#up_progress").width(0);
};

$("#post_form").ajaxForm({
	beforeSend: function() {
		$.jPlayer.pause();
	},
	error: function() {
		display_error(
			'Unable to post. Please check your Internet connection.'
		);
	},
	complete: function(xhr) {
		$("#up_progress").width(0);
		var result = JSON.parse(xhr.responseText);
		if( ! result.success ) {
			return display_error( result.response );
		}

		$("#desc").val("");
		$("#audio_effect").val('original');
		$("#loading, #post_form").hide();
		$("#post").show();
		unfinished_audio('stop');
		return display_info(result.response);
	},
});

/************************* REPLIES *************************/

$("#form_reply").ajaxForm({
	beforeSend: function() {
		$("#reply_box, #reply_options button")
			.attr('disabled', 'disabled');
	},
	error: function() {
		display_error('There was an error while adding your reply.');
		$("#reply_box, #reply_options button").removeAttr('disabled');
	},
	complete: function( xhr ) {
		$("#reply_box, #reply_options button").removeAttr('disabled');
		var result = xhr.responseText;
		if( is_JSON(result) ) {
			result = JSON.parse(result);
			return display_error( result.response );
		}

		$("#reply_box").val("");
		$("#label_reply").removeClass('active');
		$("#noreplies").remove();

		if( null === document.getElementById("load_more") ) {
			$("#replies").prepend(result);
		} else {
			$("#load_more").before(result);
		}

	}
});

$("#replies_box").on('keyup keydown', function(e) {
	var value = $(this).val();

	if( $.trim(value).length > 0 ) {
		$("#c_submit").removeAttr('disabled');
	} else {
		$("#c_submit").attr('disabled', 'disabled');
	}

});

/************************* SETTINGS *************************/

$("#settings_form").ajaxForm({
	beforeSend: function() {
		$("#settings_form button").attr('disabled', 'disabled');
	},
	error: function() {
		$("#settings_form button").removeAttr('disabled');
		display_error(
			'Could not update your settings.' + 
			'Please check your Internet connection.'
		);
	},
	complete : function(xhr) {
		$("#settings_form button").removeAttr('disabled');

		var result = JSON.parse(xhr.responseText);
		if( result.success ) {
			return display_info( result.response );
		}

		display_error( result.response );
	}
});

$("#search_type").on( 'change', function() {
	if( $(this).val() == 'a' ) {
		$("#search_sort").removeAttr('disabled');
		$('#search_sort').material_select();
	}else{
		$('#search_sort').material_select('destroy');
		$("#search_sort").attr('disabled', 'disabled');
	}
});