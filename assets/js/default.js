var recorder, context, initialized = null, init, cancel = false, cleft_i, cleft = 3, r_count = null, r_i, is_recording = false, tmp_preview_url, playeds = [], tmp_post_preview, load_more, first_second = false;
function in_array( needle, haystack ) {
	for(var i = 0; i < haystack.length; i++)
		if( needle == haystack[i])
			return true;
	return false;
}
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
function can_record() {
	navigator.getMedia = ( navigator.getUserMedia ||
                       navigator.webkitGetUserMedia ||
                       navigator.mozGetUserMedia ||
                       navigator.msGetUserMedia );
	if( ! (navigator.getMedia) )
		return false;
	window.AudioContext = window.AudioContext || window.webkitAudioContext;
	context = new AudioContext();
	if( ! context )
		return false;
	return true;
}
function startRecording() {
	if( cleft >= 3 )
		cleft_i = setInterval(startRecording, 1000);
	if( cleft > 0 ) {
		cleft -= 1;
		$(cleftn).html( String(cleft) );
		return;
	}
	if( cleft === 0 ) {
		clearInterval(cleft_i);
		cleft_i = null;
		cleft = 4;
		$("#cleft").hide();
		recorder.record();
		is_recording = true;
		recording();
	}
}
function recording() {
	if( null === r_count ) {
		r_i = setInterval(recording, 1000);
		first_second = true;
		r_count = 0;
	}
	if( first_second ) {
		first_second = false;
		return;
	}
	if( r_count < 120 ) {
		r_count += 1;
		var result, first, second;
		if( r_count < 60 ){
			first = 0;
			second = String(r_count);
		}else{
			first = Math.floor(r_count / 60 );
			second = String(r_count - (first*60) );
		}
		if( second.length === 1 )
			second = "0" + second;
		result = String( first ) + ':' + second;
		$("#count").html(result);
		return;
	}
	if( r_count === 120 ) {
		Materialize.toast('Time is up!', 5000, 'rounded');
		window.setTimeout( function() {
			$("#stop").trigger('click');
		}, 2500);
	}
}
function up_form( voice ) {
	$("#record_form").hide();
	$("#post").hide();
	$("#whatsloading").html('Uploading...');
	$("#loading").show();
	voice = voice || false;
	var ajaxform = {
		beforeSend : function() {
			$("#player_cut").jPlayer("destroy");
		},
		error : function(xhr) {
			display_error('Connection problem :(');
			$("#loading").hide();
			$("#up_progress").width(0);
			$("#post").show();
		},
		uploadProgress : function(event, position, total, percentComplete) {
			$("#up_progress").animate({
				width: percentComplete + '%'
			});
		},
		complete : function(xhr) {
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
			load_post_form(result.extra.id, result.extra.tmp_url);
		}
	};
	if( voice && recorder ) {
		recorder.exportMP3( function(blob) {
			var reader = new FileReader();
			reader.onload = function(event) {
				var fd = {};
				fd.is_voice = 'true';
				fd.bin =  event.target.result;
				ajaxform.data = fd;
				$("#up_form").ajaxSubmit(ajaxform);
			};
			reader.readAsDataURL(blob);
		});
		return;
	}
	ajaxform.data = {is_voice : 'false'};
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
	init = function() {
		if( ! can_record() )
			return false;
		if( initialized )
			return null;
		navigator.getMedia({audio:true}, function(stream) {
			recorder = audioRecorder.fromSource( context.createMediaStreamSource(stream), {type: 'audio/mpeg', workerPath : workerpath, mp3LibPath : lamepath, recordAsMP3 : true });
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
			init();
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
	});
	$("#stop").on('click', function() {
		if( ! is_recording )
			return false;
		is_recording = false;
		recorder.stop();
		if( cleft_i )
			clearInterval(cleft_i);
		if( r_i )
			clearInterval(r_i);
		r_i = cleft_i = null;
		cleft = 3;
		r_count = null;
		up_form(true);
		recorder.clear();
		$("#count").html("0:00");
	});
	$("#cancel").on('click', function() {
		recorder.clear();
		if( r_i )
			clearInterval(r_i);
		if( cleft_i )
			clearInterval(cleft_i);
		r_i = cleft_i = null;
		cleft = 3;
		r_count = null;
		$("#cleftn").html("3");
		$("#count").html("0:00");
		$("#record_form").hide();
		$("div#post").show();
	});
	$("#upload").on('click', function() {
		$("#up_file").trigger('click');
		$(this).blur();
	});
	$("#up_file").on('change', function() {
		var format = $(this).val().split('.');
		format = format[ format.length -1 ];
		format = format.toLowerCase();
		if( ! in_array(format, ['mp3', 'ogg', 'aac', 'wav', 'm4a'] ) )
			return display_error('Format not allowed');
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
	if( (start >= end) || diff > 120 || diff < 0 )
		return btn.attr('disabled', 'disabled');
	return btn.removeAttr('disabled');
});
$("#cut_cancel, #post_cancel").on('click', function() {
	if( true !== confirm('Are you sure?') )
		return false;
	$("#cut_form, #post_form").hide();
	$("#post").show();
	$.jPlayer.pause();
});
$("#cut_form").ajaxForm({
	beforeSend : function() {
		$("#player_preview").jPlayer('destroy');
		$("#cut_form").hide();
		$("#whatsloading").html('Cutting...');
		$("#loading").show();
		$.jPlayer.pause();
	},
	error : function() {
		display_error('Connection problem :(');
		$("#loading").hide();
		$("#up_progress").width(0);
		$("#cut_form").show();
	},
	uploadProgress : function(event, position, total, percentComplete) {
		$("#up_progress").animate({
			width: percentComplete + '%'
		});
	},
	complete: function(xhr) {
		$("#up_progress").width('100%');
		var result = JSON.parse(xhr.responseText);
		if( ! result.success ) {
			display_error(result.response);
			$("#loading").hide();
			$("#up_progress").width(0);
			$("#cut_form").show();
			return;
		}
		load_post_form( result.extra.id, result.extra.tmp_url);
	}
});
$("#post_form").ajaxForm({
	beforeSend: function() {
		$.jPlayer.pause();
	},
	error : function() {
		display_error('Connection problem :(');
	},
	complete : function(xhr) {
		var result = JSON.parse(xhr.responseText);
		$("#desc").val("");
		$("#loading, #post_form").hide();
		$("#up_progress").width(0);
		$("#post").show();
		if( ! result.success )
			return display_error( result.response );
		return display_info(result.response);
	},
});
$(document).on('click', '.laic', function(e) {
	var _id = $(this).data('id');
	last_laic_id = $(this);
	$.ajax({
		type: "POST",
		cache: false,
		url: ajaxurl + 'favorite.php',
		data : {id: _id},
		error: function() {
			display_error('Connection problem :(');
		},
		success: function(result) {
			result = JSON.parse(result);
			if( ! result.success )
				return display_error(result.response);
			if( result.extra.action == 'favorite' )
				last_laic_id.addClass('favorited');
			else
				last_laic_id.removeClass('favorited');
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
			$( '#plays_' + playeds[ playeds.length - 1 ] ).find('span').html( result.extra.count );
		}
	});
});
$(document).on('click', '.delit', function(e) {
	var _id = $(this).data('id');
	$.ajax({
		type: "POST",
		cache: false,
		url: ajaxurl + 'delete.php',
		data : {id: _id},
		error: function() {
			display_error('Connection error :(');
		},
		success: function(result) {
			result = JSON.parse(result);
			if( ! result.success )
				return display_error(result.response);
			if( typeof is_audio === 'boolean' )
				return window.location.replace('/');
			$("#" + result.response ).fadeOut(1000, function() {
				$(this).remove();
			});
		}
	});
});
$(document).on('click', '#load_more', function() {
	var load = $(this).data('load');
	var page = $(this).data('page');
	var extra = $(this).data('extra');
	load_more = [ load, $(this).clone() ];
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
		},
		success : function( result ) {
			if( is_JSON(result) ) {
				result = JSON.parse(result);
				return display_error(result.response);
			}
			$( '#' + load_more[0] ).append( result );
			load_more = null;
		}
	});
});
$("#settings_form").ajaxForm({
	error : function() {
		display_error('Connection problem :(');
	},
	complete : function(xhr) {
		var result = JSON.parse(xhr.responseText);
		if( result.success )
			return display_info( result.response );
		display_error( result.response );
	}
});
$("#form_reply").ajaxForm({
	error : function() {
		display_error('Connection problem :(');
	},
	complete : function( xhr ) {
		var result = xhr.responseText;
		if( is_JSON(result) ) {
			result = JSON.parse(result);
			return display_error( result.response );
		}
		$("#reply_box").val("");
		$("#noreplies").remove();
		if( null === document.getElementById("load_more") )
			$("#replies").append(result);
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
$("#close_ft").on('click', function() {
	$("#firstime").hide();
});
$( "#search_type").on( 'change', function() {
	if( $(this).val() == 'a' ) {
		$("#search_sort").removeAttr('disabled');
		$('#search_sort').material_select();
	}else{
		$('#search_sort').material_select('destroy');
		$("#search_sort").attr('disabled', 'disabled');
	}
});