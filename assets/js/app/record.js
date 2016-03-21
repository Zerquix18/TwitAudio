/**
* Record file
* Handles all the data related to recording/uploading
*
* @author Zerquix18
* @copyright Copyright (c) 2016 - Luis A. Martínez
*
**/

window.record = {

	initialized: false,

	/**
	* @return bool
	**/
	can_record: function() {
		navigator.get_media = (
				navigator.getUserMedia ||
				navigator.webkitGetUserMedia ||
				navigator.mozGetUserMedia ||
				navigator.msGetUserMedia
			);

		if( ! navigator.get_media )
			return false;

		window.AudioContext = window.AudioContext ||
							  window.webkitAudioContext;

		this.context = new AudioContext();
		if( ! this.context )
			return false;

		return true;
	},
	/**
	* @return void
	**/
	init: function() {
		if( ! this.can_record() )
			return;
		if( this.initialized )
			return;

		navigator.get_media({
				audio: true
			},

			function(stream) { // success:
				window.record.recorder =
					audioRecorder.fromSource(
						window.record
						.context.createMediaStreamSource(stream), {
							type: 'audio/mpeg',
							/**
							* these 2 variables are set in
							* templates/footer.phtml
							**/
							workerPath : workerpath,
							mp3LibPath : lamepath,
							recordAsMP3 : true,
							channels: 1
						}
					);
				window.record.initialized = true;
				$("div#waiting").hide();
				// now this initialized is true... 
				window.record.start();
			},

			function() { // error:
				window.record.initialized = false;
				$("div#waiting").hide();
				$("div#post").show();
				display_error(
					'Microphone access is not allowed or was blocked.'
				);
			});
	}, // init func

	start: function() {
		if( ! this.initialized ) {
			$("div#post").hide();
			$("div#waiting").show();
			this.init();
			return;
		}

		$("#post").hide();
		$("#record_form").show();
		$("#cleft").show();
		this.counter_left();
		unfinished_audio('start');
	},

	stop: function() {
		if( ! this.is_recording )
			return;
		
		this.is_recording = false;
		this.recorder.stop();
		if( 'undefined' !== typeof this.seconds_left_interval )
			clearInterval( this.seconds_left_interval);
		if( 'undefined' !== typeof this.recording_seconds_interval );
			clearInterval( this.recording_seconds_interval );

		delete this.seconds_left_interval;
		delete this.recording_seconds_interval;
		delete this.recording_seconds;
		delete this.seconds_left;

		window.upload_audio( { 'is_voice' : true } );
		this.recorder.clear();
		$("#count").html("0:00");
	},

	cancel: function() {
		this.recorder.clear();

		if( 'undefined' !== typeof this.seconds_left_interval )
			clearInterval( this.seconds_left_interval );
		if( 'undefined' !== typeof this.recording_seconds_interval );
			clearInterval( this.recording_seconds_interval );

		delete this.seconds_left_interval;
		delete this.recording_seconds_interval;
		delete this.recording_seconds;
		delete this.seconds_left;

		$("#cleftn").html("3");
		$("#count").html("0:00");
		$("#record_form").hide();
		$("div#post").show();
		unfinished_audio('stop');
	},

	/**
	* starts a countdown of 3 seconds BEFORE
	* to start recording
	**/
	counter_left: function() {
		// static variable
		if( 'undefined' == typeof this.seconds_left )
			this.seconds_left = 3;

		if( 'undefined' == typeof this.seconds_left_interval ) {
			this.seconds_left_interval = setInterval(
					this.counter_left.bind(this),
					1000
				);
			return;
		}

		if( this.seconds_left > 0 ) {
			this.seconds_left -= 1;
			$("#cleftn").text( String(this.seconds_left) );
			return;
		}
		if( 0 === this.seconds_left ) {
			// initialize everything
			clearInterval(this.seconds_left_interval);
			delete this.seconds_left;
			delete this.seconds_left_interval;
			$("#cleft").hide();
			window.record.recorder.record(); // starts recording!
			this.is_recording = true;
			
			this.update_seconds();
		}
	},
	/**
	* updates the seconds while recording
	* 'max_duration' is declared in templates/footer.phtml
	**/
	update_seconds: function() {
		if( 'undefined' === typeof this.recording_seconds_interval ) {
			this.recording_seconds_interval = setInterval(
				this.update_seconds.bind(this),
				1000
			);
			this.recording_seconds = 0;
			return;
		}
		// start increasing
		if( this.recording_seconds < max_duration ) {
			this.recording_seconds += 1;

			var result, first_number, second_number;

			if( this.recording_seconds < 60 ){
				first_number  = 0;
				second_number = this.recording_seconds;
			}else{
				first_number = Math.floor(this.recording_seconds / 60 );
				second_number = this.recording_seconds - (first_number*60);
			}
			if( String(second_number).length === 1 )
				second_number = "0" + String(second_number);

			result = String( first_number ) + ':' + String(second_number);

			$("#count").text(result);
			return;
		}

		if( max_duration === this.recording_seconds ) {
			Materialize.toast('Time is up!', 5000, 'rounded');
			this.stop();
		}
	},
};


/** LISTENERS **/

// don't call the functions directly
// or 'this' will be overwritten

$("#record").on('click', function() {
	window.record.start();
});
$("#stop")  .on('click', function() {
	window.record.stop();
});
$("#cancel").on('click', function() {
	window.record.cancel();
});