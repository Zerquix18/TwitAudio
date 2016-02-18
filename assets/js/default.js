/**
* Javascript code for TwitAudio
* Author: Zerquix18 <zerquix18@hotmail.com>
* Copyright (c) 2016 - Luis A. Martínez
**/

var	recorderJS,
	context, //AudioContext object
	initMicrophone,
	/** intervals **/
	counterLeft = 3, // before recording, seconds left to start
	counterLeft_interval,
	recordingSeconds = null,
	recordingSeconds_interval,
	// 
	tmp_preview_url,
	tmp_post_preview,
	load_more,
	initialized = null, // if everything with the mic was ok
	cancel = false,
	recordingSeconds = null,
	is_recording = false, // it says it all
	playeds = [], // played audios in page
	first_second = false,
	unloaded = false,
	progressives = [],
	can_scroll = true,
	all_effects_loaded = true,
	load_effects_interval = null,
	added_files = [],
	added_effects = [],
	save_my_life = -1;
	//^I gotta explain myself.. idk,
	// I finished 2 hours of bugs
	// with that variable
/**
* Checks if needle is in haystack
* in_array('lol', ['asd', 'lol']) = true
*
**/
function in_array( needle, haystack ) {
	for(var i = 0; i < haystack.length; i++)
		if( needle == haystack[i])
			return true;
	return false;
}
// displays a toast
function display_error( error, dissapear ) {
	var text = '<i class="fa fa-close"></i>&nbsp;';
	text += error;
	dissapear = dissapear || 5000;
	Materialize.toast(text, dissapear, 'rounded');
}
function display_info( info, dissapear ) {
	var text = '<i class="fa fa-check"></i>&nbsp;';
	text += info;
	dissapear = dissapear || 5000;
	Materialize.toast(text, dissapear, 'rounded');
}
// returns (bool)
function can_record() {
	navigator.getMedia = (
			navigator.getUserMedia ||
			navigator.webkitGetUserMedia ||
			navigator.mozGetUserMedia ||
			navigator.msGetUserMedia
		);
	if( ! navigator.getMedia ) // none of them worked
		return false;
	window.AudioContext = window.AudioContext || window.webkitAudioContext;
	context = new AudioContext();
	if( ! context )
		return false;
	return true;
}
function startRecording() {
	if( counterLeft >= 3 )
		counterLeft_interval = setInterval(startRecording, 1000);
	if( counterLeft > 0 ) {
		counterLeft -= 1;
		$(cleftn).html( String(counterLeft) );
		return;
	}
	if( 0 === counterLeft ) {
		clearInterval(counterLeft_interval);
		counterLeft_interval = null;
		counterLeft = 4;
		$("#cleft").hide();
		recorderJS.record(); // here it starts recording
		is_recording = true;
		recording();
	}
}
function recording() {
	if( null === recordingSeconds ) {
		recordingSeconds_interval = setInterval(recording, 1000);
		first_second = true;
		recordingSeconds = 0;
	}
	if( first_second ) {
		first_second = false;
		return;  // don't start counting till 1 second
	}
	if( recordingSeconds < max_duration ) {
		recordingSeconds += 1;
		var result, firstNumber, secondNumber;
		if( recordingSeconds < 60 ){
			firstNumber = 0;
			secondNumber = String(recordingSeconds);
		}else{
			firstNumber = Math.floor(recordingSeconds / 60 );
			secondNumber = // ↓
			String( recordingSeconds - (firstNumber*60) );
		}
		if( secondNumber.length === 1 )
			secondNumber = "0" + secondNumber;
		result = String( firstNumber ) + ':' + secondNumber;
		$("#count").html(result);
		return;
	}
	if( max_duration === recordingSeconds ) {
		Materialize.toast('Time is up!', 5000, 'rounded');
		$("#stop").trigger('click');
	}
}
function up_form( is_voice ) {
	$("#record_form").hide();
	$("#post").hide();
	show_progressive('#whatsloading', [
			'Uploading...',
			'Stalking my ex on Twitter...',
			"Looking for Jhon Cena but I can't see him...",
			'Pushing it up...',
			'This is a really long audio...',
			"I'll have to use a lift to upload this",
			'Uploading...'
		], 5); // 5 seconds of separation
	$("#loading").show();
	is_voice = is_voice || false;
	var ajaxform = {
		beforeSend : function() {
			$("#player_cut").jPlayer("destroy");
		},
		error : function(xhr) {
			display_error('There was an error while uploading your audio. Please check your Internet connection');
			$("#loading").hide();
			$("#up_progress").width(0);
			$("#post").show();
			stop_progressive();
		},
		uploadProgress : function(event, position, total, percentComplete) {
			$("#up_progress").animate({
				width: percentComplete + '%'
			});
		},
		complete : function(xhr) {
			stop_progressive();
			$("#up_progress").width("100%");
			var result = JSON.parse(xhr.responseText);
			if( false === result.success ) {
				if( typeof result.extra == 'undefined' ) {
					$("#loading").hide();
					$("#up_progress").width(0);
					$("#post").show();
					display_error( result.response );
					return;
				}
				unfinishedaudio('start');
				tmp_preview_url = result.extra.tmp_url;
				$("#player_cut").jPlayer({
					ready: function(event) {
						$(this).jPlayer("setMedia", {
						mp3: tmp_preview_url,
					});
					},
					cssSelectorAncestor : '#cut_container',
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
				$("#audio_id").val( result.extra.id );
				return;
			}
			unfinishedaudio('start');
			load_effects( result.extra.id );
			load_post_form(result.extra.id, result.extra.tmp_url);
			$(".original").data('url', result.extra.tmp_url);
		}
	};
	if( is_voice && recorderJS ) {
		recorderJS.exportMP3( function(blob) {
			var reader = new FileReader();
			reader.onload = function(event) {
				var fileReader = {};
				fileReader.is_voice = '1';
				fileReader.bin =  event.target.result;
				ajaxform.data = fileReader;
				$("#up_form").ajaxSubmit(ajaxform);
			};
			reader.readAsDataURL(blob);
		});
		return;
	}
	ajaxform.data = {is_voice : '0'};
	$("#up_form").ajaxSubmit(ajaxform);
}
function load_post_form( id, tmp_url ) {
	$("#a_id").val(id);
	tmp_post_preview = tmp_url;
	$("#player_preview").jPlayer({
		ready: function(event) {
			$(this).jPlayer("setMedia", {
			mp3: tmp_post_preview,
		});
		},
		cssSelectorAncestor : '#preview_container',
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
}
function is_JSON(str) {
	return (/^[\],:{}\s]*$/.test(str.replace(/\\["\\\/bfnrtu]/g, '@').replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']').replace(/(?:^|:|,)(?:\s*\[)+/g, '')));
}
function readCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
		while (c.charAt(0)==' ')
			c = c.substring(1,c.length);
		if(c.indexOf(nameEQ) === 0)
			return c.substring(nameEQ.length,c.length);
    	}
    	return null;
}
function norecordsupport() {
	$("#or, #record").hide();
	$("#upload").css('float', 'none');
	var g = readCookie('norecordsupport');
	if( null !== g && '' !== g )
		return;
	$("#norecordsupport").show();
	document.cookie = 'norecordsupport=1';
}
function unfinishedaudio_helper( e ) {
	var confirmationMessage = "You haven't finished uploading your audio. Are you sure you want to leave?";
	(e || window.event).returnValue = confirmationMessage;
	return confirmationMessage;
}
function unfinishedaudio( action ) {
	if( 'start' === action && ! unloaded ) {
		unloaded = true;
		return window.addEventListener(
				'beforeunload',
				unfinishedaudio_helper
			);
	}
	else if( 'stop' === action && unloaded ) {
		unloaded = false;
		return window.removeEventListener(
				'beforeunload',
				unfinishedaudio_helper
			);
	}
	return void 0;
}
function show_progressive( selector, texts, time ) {
	if( typeof texts !== 'object' )
		return;
	time = time || 5;
	var milliSeconds;
	for( var i = 0; i < texts.length; i++ ) {
		milliSeconds = time * (i * 1000);
		var id = window.setTimeout(
			function( selector, text) {
				$(selector).text(text);
			},
			milliSeconds,
			selector,
			texts[i]
		);
		progressives.push(id);
	}
}
function stop_progressive() {
	for( var i = 0; i < progressives.length; i++)
		window.clearTimeout( progressives[i] );
}
function load_effects( audio_id ) {
	if( ! all_effects_loaded )
		return;
	if( null === load_effects_interval ) {
		load_effects_interval = setInterval(
				load_effects,
				5000,
				audio_id
			);
		return; // wait the 5 seconds...
	}
	// proceed
	var params = {
		'id': audio_id
	};
	$.ajax({
		type: "POST",
		cache: false,
		url: ajaxurl + 'checkeffects.php',
		data: params,
		success: function( result ) {
			result = JSON.parse(result);
			if( ! result.success ) {
				clearInterval( load_effects_interval );
				return display_error( result.response );
			}
			/* fun starts here */
			/** load all the audios with effects **/
			var loaded_effects = result.extra.loaded_effects;
			for( var i = 0; i < loaded_effects.length; i++) {
				// 2 keys: file for the path,
				// and name for the effect name
				var name = loaded_effects[i].name;
				if( in_array(name, added_effects) )
					continue;
				else
					added_effects.push(name);
				added_files.push( loaded_effects[i].file );
				$("." + name).data('url', loaded_effects[i].file);
				$("#effect_preview_" + name).jPlayer({
					ready: function(event) {
						save_my_life++;
						// ^ now when this motherfucking function
						// executes, it gets the right URL ↓
						$(this).jPlayer("setMedia", {
							mp3: added_files[save_my_life],
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
			if( result.extra.are_all_loaded ) {
				clearInterval(load_effects_interval);
				all_effects_loaded = true;
				added_effects = [];
			}
		}
	});
}
/** event listeners **/
$(document).ready( function() {
	$('.collapsible').collapsible();
	$('.button-collapse').sideNav({ menuWidth: 240, edge: 'left', closeOnClick: false });
	$('select').material_select();
	$('.modal-trigger').leanModal({
		dismissible: true,
		opacity: 0.5,
		in_duration: 300,
		out_duration: 200,
	});

	if( ! can_record() )
		norecordsupport();

	initMicrophone = function() {
		if( ! can_record() )
			return false;
		if( initialized )
			return null;
		navigator.getMedia(
				{
					audio: true
				},
				function(stream) {
					recorderJS =
						audioRecorder.fromSource(
							context.createMediaStreamSource(stream),
							{
								type: 'audio/mpeg',
								workerPath : workerpath,
								mp3LibPath : lamepath,
								recordAsMP3 : true,
								channels: 1
							}
						);
					initialized = true;
					if( cancel ) {
						clearInterval(cancel);
						cancel = false;
					}
					$("div#waiting").hide();
					$("div#record").trigger('click');
				}, function(e) {
					initialized = false;
					$("div#waiting").hide();
					$("div#post").show();
					display_error('Microphone access is not allowed or was blocked.');
				});
			};
	$("div#record").on('click', function() {
		if( ! initialized ) {
			$("div#post").hide();
			$("div#waiting").show();
			initMicrophone();
			cancel = window.setTimeout( function() {
				$("div#waiting").hide();
				$("div#post").show();
			}, 10000);
			return;
		}
		$("#post").hide();
		$("#record_form").show();
		$("#cleft").show();
		startRecording();
		unfinishedaudio('start');
	});
	$("#stop").on('click', function() {
		if( ! is_recording )
			return false;
		is_recording = false;
		recorderJS.stop();
		if( counterLeft_interval )
			clearInterval(cleft_i);
		if( recordingSeconds_interval )
			clearInterval(recordingSeconds_interval);
		counterLeft_interval =
		recordingSeconds_interval = null;
		counterLeft = 3;
		recordingSeconds = null;
		up_form(true);
		recorderJS.clear();
		$("#count").html("0:00");
	});
	$("#cancel").on('click', function() {
		recorderJS.clear();
		if( recordingSeconds_interval )
			clearInterval(recordingSeconds_interval);
		if( counterLeft_interval )
			clearInterval(counterLeft_interval);
		counterLeft_interval =
		recordingSeconds_interval = null;
		counterLeft = 3;
		recordingSeconds = null;
		$("#cleftn").html("3");
		$("#count").html("0:00");
		$("#record_form").hide();
		$("div#post").show();
		unfinishedaudio('stop');
	});
	$("#upload").on('click', function() {
		$("#up_file").trigger('click');
		$(this).blur();
	});
	$("#up_file").on('change', function() {
		var format = $(this).val().split('.');
		var file_size = this.files[0].size/1024/1024;
		format = format[ format.length -1 ];
		format = format.toLowerCase();
		if( ! in_array(format, ['mp3', 'ogg', 'aac', 'wav', 'm4a'] ) )
			return display_error('Format not allowed');
		if( file_size > upload_file_limit )
			return display_error(
				'The file size is greater than your current ' +
				'limit \'s, ' + upload_file_limit + ' mb');
		up_form();
	});
});
$("#end, #start").on('keyup', function() {
	var lel, btn = $("#cut_button"),
	start = $("#start").val(),
	end = $("#end").val();
	if( ! $.isNumeric( start ) ) {
		if( ! /^([0-9]{1,2}):([0-9]{1,2})$/.test( start ) )
			return btn.attr('disabled', 'disabled');
		lel = start.split(':');
		start = ( parseInt(lel[0]) * 60 ) + parseInt(lel[1]);
	}
	if( ! $.isNumeric( end ) ) {
		if( ! /^([0-9]{1,2}):([0-9]{1,2})$/.test( end ) )
			return btn.attr('disabled', 'disabled');
		lel = end.split(':');
		end = ( parseInt(lel[0]) * 60 ) + parseInt(lel[1]);
	}
	var diff = end-start;
	if( (start >= end) || diff > max_duration || diff < 1 )
		return btn.attr('disabled', 'disabled');
	return btn.removeAttr('disabled');
});
$("#cut_cancel, #post_cancel").on('click', function() {
	if( true !== confirm('Are you sure?') )
		return false;
	$("#cut_form, #post_form").hide();
	$("#post").show();
	$.jPlayer.pause();
	unfinishedaudio('stop');
});
$("#cut_form").ajaxForm({
	beforeSend : function() {
		$("#player_preview").jPlayer('destroy');
		$("#cut_form").hide();
		$("#up_progress").removeClass('determinate')
				     .addClass('indeterminate');
		show_progressive( '#whatsloading', [
				'Cutting...',
				'Looking for my scissors...',
				'Nice audio by the way...',
				'Have you considered to take singing classes?',
				'This audio is so deep I see Adele rolling on it',
				'This is taking too long...',
				'Cutting...'
			], 3);
		$("#loading").show();
		$.jPlayer.pause();
	},
	error : function() {
		stop_progressive();
		display_error(
			'There was a problem while cutting your audio. Please check your Internet connection',
			10000
		);
		$("#loading").hide();
		$("#up_progress").removeClass('indeterminate')
				.addClass('determinate');
		$("#cut_form").show();
	},
	complete: function(xhr) {
		stop_progressive();
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
		load_effects( result.extra.id );
		load_post_form( result.extra.id, result.extra.tmp_url);
		$(".original").data('url', result.extra.tmp_url);
	}
});
$("#post_form").ajaxForm({
	beforeSend: function() {
		$.jPlayer.pause();
	},
	error : function() {
		display_error("Unable to post. Please check your Internet connection.");
	},
	complete : function(xhr) {
		$("#up_progress").width(0);
		var result = JSON.parse(xhr.responseText);
		if( ! result.success )
			return display_error( result.response );
		$("#desc").val("");
		$("#audio_effect").val('original');
		$("#loading, #post_form").hide();
		$("#post").show();
		unfinishedaudio('stop');
		return display_info(result.response);
	},
});
$(document).on('click', '.laic', function(e) {
	var _id = $(this).data('id');
	last_laic_id = $(this);
	var parms = {
		id: _id,
		action: $(this).hasClass('favorited') ? // faved already?
			'unfav' : 'fav'
	};
	$.ajax({
		type: "POST",
		cache: false,
		url: ajaxurl + 'favorite.php',
		data : parms,
		beforeSend: function() {
			// FAKE AJAX
			var att = last_laic_id.find('span');
			var c = parseInt(att.text());
			if( last_laic_id.hasClass('favorited') ) { // already faved?
				last_laic_id.removeClass('favorited');
				att.text( String(c - 1) ); // decrease 1
			}else{
				last_laic_id.addClass('favorited');
				att.text( String(c + 1) ); // increase 1
			}
		},
		error: function() {
			var att = last_laic_id.find('span');
			var c = parseInt(att.text());
			if( last_laic_id.hasClass('favorited') ){ // back to normal
				last_laic_id.removeClass('favorited');
				att.text( String(c - 1) ); // decrease 1
				display_error('There was a problem while favoriting the audio...');
			}else{
				last_laic_id.addClass('favorited');
				att.text( String(c + 1) ); // increase 1
				display_error('There was a problem while unfavoriting the audio...');
			}
		},
		success: function(result) {
			result = JSON.parse(result);
			if( ! result.success ) {
				var att = last_laic_id.find('span');
				var c = parseInt(att.text());
				if( last_laic_id.hasClass('favorited') ){
					// back to normal
					last_laic_id.removeClass('favorited');
					att.text( String(c - 1) ); // decrease 1
				}else{
					att.text( String(c + 1) ); // increase 1
					last_laic_id.addClass('favorited');
				}
				return display_error(result.response);
			}
			last_laic_id.find('span').html(result.extra.count);
		}
	});
});
$(document).on('click', '.plei', function(e) {
	var _id = $(this).data('id');
	if( in_array(_id, playeds) )
		return;
	playeds.push(_id);
	$.ajax({
		type: "POST",
		cache: false,
		url: ajaxurl + 'play.php',
		data : {id: _id},
		success: function(result) {
			result = JSON.parse(result);
			if( ! result.success )
				return;
			$( '#plays_' + playeds[ playeds.length - 1 ] )
				.find('span').html( result.extra.count );
		}
	});
});
$(document).on('click', '.delit', function(e) {
	if( true !== confirm('Are you sure you want to delete this audio?') )
		return false;
	var _id = $(this).data('id');
	$.ajax({
		type: "POST",
		cache: false,
		url: ajaxurl + 'delete.php',
		data : {id: _id},
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
			if( typeof audio_id !== 'undefined' && result.response == audio_id )
				return window.location.replace('/');
			$(".audio_" + result.response ).fadeOut(1000, function() {
				$(this).remove();
			});
		}
	});
});
$(window).scroll( function() {
	if( (
		( $(window).scrollTop() + $(window).height() ) >
		( $(document).height() - 50 ) 
		) === false
	)
	return false;
	if( null === document.getElementById("load_more") )
			return false;
	if( ! can_scroll )
		return false;
	_lm = $("#load_more"); // lm = load more
	var load = _lm.data('load');
	var page = _lm.data('page');
	var extra = _lm.data('extra');
	load_more = [ load, _lm.clone() ];
	var to_load;
	var data = {};
	if( 'search' == load ){
		to_load = search;
		data.s = sort;
		data.t  = type;
	} else if('audios' == load || 'favorites' == load )
		to_load = profile;
	else if('replies' == load ) {
		to_load = audio_id;
		data.reply_to = linked;
	}else
		return;
	data.p = page;
	data.q = to_load;
	$.ajax({
		type: "POST",
		cache: false,
		url: ajaxurl + load + '.php',
		data : data,
		beforeSend : function() {
			$("#load_more").remove();
		},
		error : function() {
			$( '#' + load_more[0] ).append( load_more[1] );
			load_more = null;
			display_error('There was an error while loading the content... Please check your Internet connection.');
			can_scroll = true;
		},
		success : function( result ) {
			if( is_JSON(result) ) {
				result = JSON.parse(result);
				return display_error(result.response);
			}
			$( '#' + load_more[0] ).append( result );
			load_more = null;
			can_scroll = true;
		}
	});
});
$("#settings_form").ajaxForm({
	beforeSend: function() {
		$("#settings_form button").attr('disabled', 'disabled');
	},
	error : function() {
		$("#settings_form button").removeAttr('disabled');
		display_error('Could not update your settings. Please check your Internet connection.');
	},
	complete : function(xhr) {
		$("#settings_form button").removeAttr('disabled');
		var result = JSON.parse(xhr.responseText);
		if( result.success )
			return display_info( result.response );
		display_error( result.response );
	}
});
$("#form_reply").ajaxForm({
	beforeSend: function() {
		$("#reply_box, #reply_options button").attr('disabled', 'disabled');
	},
	error : function() {
		display_error('There was an error while adding your reply.');
		$("#reply_box, #reply_options button").removeAttr('disabled');
	},
	complete : function( xhr ) {
		var result = xhr.responseText;
		$("#reply_box, #reply_options button").removeAttr('disabled');
		if( is_JSON(result) ) {
			result = JSON.parse(result);
			return display_error( result.response );
		}
		$("#reply_box").val("");
		$("#label_reply").removeClass('active');
		$("#noreplies").remove();
		if( null === document.getElementById("load_more") )
			$("#replies").prepend(result);
		else
			$("#load_more").before(result);
	}
});
$("#replies_box").on('keyup keydown', function(e) {
	var val = $(this).val();
	if( $.trim(val).length > 0 )
		$("#c_submit").removeAttr('disabled');
	else
		$("#c_submit").attr('disabled', 'disabled');
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
$(".choose_effect").on('click', function(e) {
	e.preventDefault();
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