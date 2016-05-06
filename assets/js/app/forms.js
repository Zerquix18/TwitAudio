/**
* Functions and listeners for forms
*
* @author Zerquix18
*
**/

/************************* UPLOAD *************************/

/**
* Uploads an audio
* If options.isVoice = true then
* it will upload the binary of the recorder
* if not, it will upload the 'up_file' input
*
**/
window.uploadAudio = function( options ) {
		var isVoice = options.isVoice || false;
		$("#record_form").hide();
		$("#post").hide();
		window.progressiveText.start(
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
		var uploadForm = {
			beforeSend: function() {
				$("#player_cut").jPlayer("destroy");
				window.effects.clean();
			},
			error: function( xhr ) {
				displayError(
					'There was an error while uploading your audio. ' + 
					'Please check your Internet connection');
				$("#loading").hide();
				$("#up_progress").width(0);
				$("#post").show();
				window.progressiveText.stop('#whatsloading');
				$("#up_form").trigger('reset');
			},
			uploadProgress: function( event, position, total, percent ) {
				$("#up_progress").animate({
					width: percent + '%'
				});
			},
			complete: function( xhr ) {
				window.progressiveText.stop('#whatsloading');
				$("#up_form").trigger('reset');
				$("#up_progress").width("100%");

				var result = JSON.parse(xhr.responseText);
				var tmpUrl = result.tmp_url || '';
				var id     = result.id || '';

				if( false === result.success ) {
					if( ! tmpUrl ) {
						// there was an error
						// and it's not because it's too long
						$("#loading").hide();
						$("#up_progress").width(0);
						$("#post").show();
						displayError( result.response );
						return;
					}
					// needs cut
					unfinishedAudio('start');
					window.tmpUrl = tmpUrl;
					// make it global
					$("#player_cut").jPlayer({
						ready: function(event) {
							$(this).jPlayer("setMedia", {
								// so this guy catches it
								mp3: window.tmpUrl,
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
				$(".original").data('url', tmpUrl);

				unfinishedAudio('start');

				window.effects.load(result.id);
				window.effects.showLoading(result.effects);
				window.preparePostForm(result.id, result.tmp_url);
			}
		};
		if( isVoice && window.record.recorder ) {
			/**
			* If it's voice then we'll upload the
			* base64 as 'bin'
			**/
			window.record.recorder.exportMP3( function(blob) {
				var reader = new FileReader();
				reader.onload = function(event) {
					var _fileReader      = {};
					_fileReader.is_voice = '1';
					_fileReader.bin      =  event.target.result;
					uploadForm.data      = _fileReader;
					$("#up_form").ajaxSubmit(uploadForm);
				};
				reader.readAsDataURL(blob);
			});
			return;
		}
		uploadForm.data = {is_voice: '0'};
		$("#up_form").ajaxSubmit(uploadForm);
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

	var format   = $(this).val().split('.');
	var fileSize = this.files[0].size / 1024 / 1024;

	format = format[ format.length - 1 ];
	format = format.toLowerCase();

	if( ! inArray(format, ['mp3', 'ogg'] ) ) {
		return displayError('Format not allowed');
	}

	/*
	* uploadFileLimit is defined in templates/footer.phtml
	*/ 
	if( fileSize > uploadFileLimit ) {
		return displayError(
			'The file size is greater than your current ' +
			'limit \'s, ' + uploadFileLimit + ' mb');
	}

	window.uploadAudio( {isVoice: false } );
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
		$("#up_progress")
			.removeClass('determinate')
			.addClass('indeterminate');

		window.progressiveText.start(
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
		progressiveText.stop('#whatsloading');
		displayError(
			'There was a problem while cutting your audio. Please check your Internet connection',
			10000
		);
		// get everything back
		$("#loading").hide();
		$("#up_progress")
			.removeClass('indeterminate')
			.addClass('determinate');
		$("#cut_form").show();
	},
	complete: function(xhr) {
		var result = JSON.parse(xhr.responseText);
		var tmpUrl = result.tmp_url;
		var id     = result.id;

		progressiveText.stop('#whatsloading');
		$("#up_progress")
			.removeClass('indeterminate')
			.addClass('determinate');

		if( ! result.success ) {
			displayError(result.response);
			$("#loading").hide();
			$("#cut_form").show();
			return;
		}

		$("#cut_form").trigger('reset');
		$(".original").data('url', tmpUrl);

		effects.load(id);
		effects.showLoading(result.effects);

		window.preparePostForm(id, tmpUrl);
	}
});

/*
* Will validate the #cut_form inputs
*/

$("#end, #start").on('keyup', function() {

	var numbers;
	var diff;
	var btn   = $("#cut_button");
	var start = $("#start").val();
	var end   = $("#end")  .val();
	var isNumeric = function( value ) {
			return /^[0-9]{0,3}$/.test(value);
		};

	if( ! isNumeric(start) ) {

		if( ! /^([0-9]{1,2}):([0-9]{1,2})$/.test(start) ) {
			return btn.attr('disabled', 'disabled');
		}

		numbers = start.split(':');
		start   = ( parseInt(numbers[0]) * 60 ) + parseInt(numbers[1]);
	}else {
		start   = parseInt( start );
	}

	if( ! isNumeric(end) ) {

		if( ! /^([0-9]{1,2}):([0-9]{1,2})$/.test(end) ) {
			return btn.attr('disabled', 'disabled');
		}

		numbers = end.split(':');
		end     = ( parseInt(numbers[0]) * 60 ) + parseInt(numbers[1]);
	}else {
		end     = parseInt( end );
	}

	diff = end-start;
	/* maxDuration is declared in templates/footer.phtml */
	if( (start >= end) || diff > maxDuration || diff < 1 ) {
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
	unfinishedAudio('stop');
});

/************************* POST *************************/

window.preparePostForm = function( id, tmpUrl ) {

	$("#a_id").val(id);

	window.tmpPostPreview = tmpUrl;
	// made it global

	$("#player_preview").jPlayer({
		ready: function(event) {
			$(this).jPlayer("setMedia", {
				mp3: window.tmpPostPreview,
				// so this guys catches it
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
		displayError(
			'Unable to post. Please check your Internet connection.'
		);
	},
	complete: function(xhr) {
		$("#up_progress").width(0);
		var result = JSON.parse(xhr.responseText);
		if( ! result.success ) {
			return displayerror( result.response );
		}

		$("#desc").val("");
		$("#audio_effect").val('original');
		$("#loading, #post_form").hide();
		$("#post").show();
		unfinishedAudio('stop');
		return displayInfo(result.response);
	},
});

/************************* REPLIES *************************/

$("#form_reply").ajaxForm({
	beforeSend: function() {
		$("#reply_box, #reply_options button")
			.attr('disabled', 'disabled');
	},
	error: function() {
		displayError('There was an error while adding your reply.');
		$("#reply_box, #reply_options button").removeAttr('disabled');
	},
	complete: function( xhr ) {
		$("#reply_box, #reply_options button").removeAttr('disabled');
		var result = xhr.responseText;
		if( isJson(result) ) {
			result = JSON.parse(result);
			return displayError(result.response);
		}

		$("#reply_box").val('');
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
		displayError(
			'Could not update your settings.' + 
			'Please check your Internet connection.'
		);
	},
	complete : function(xhr) {
		$("#settings_form button").removeAttr('disabled');

		var result = JSON.parse(xhr.responseText);
		if( result.success ) {
			return displayInfo(result.response);
		}

		displayError(result.response);
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