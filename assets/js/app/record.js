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
	canRecord: function() {
		navigator.getMedia = (
				navigator.getUserMedia ||
				navigator.webkitGetUserMedia ||
				navigator.mozGetUserMedia ||
				navigator.msGetUserMedia
			);

		if( ! navigator.getMedia ) {
			return false;
		}

		window.AudioContext = window.AudioContext ||
							  window.webkitAudioContext;

		this.context = new AudioContext();
		if( ! this.context ) {
			return false;
		}

		return true;
	},
	/**
	* @return void
	**/
	init: function() {
		if( ! this.canRecord() ) {
			return;
		}
		if( this.initialized ) {
			return;
		}

		navigator.getMedia({
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
							workerPath : workerPath,
							mp3LibPath : lamePath,
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
				displayError(
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
		this.counterLeft();
		unfinishedAudio('start');
	},

	stop: function() {
		if( ! this.isRecording ) {
			return;
		}
		
		this.isRecording = false;
		this.recorder.stop();
		if( 'undefined' !== typeof this.secondsLeftInterval ) {
			clearInterval( this.secondsLeftInterval);
		}
		if( 'undefined' !== typeof this.recordingSecondsInterval ) {
			clearInterval( this.recordingSecondsInterval );
		}
		delete this.secondsLeft;
		delete this.recordingSeconds;
		delete this.secondsLeftInterval;
		delete this.recordingSecondsInterval;

		window.uploadAudio( {isVoice:true} );
		this.recorder.clear();
		$("#count").html("0:00");
	},

	cancel: function() {
		this.recorder.clear();

		if( 'undefined' !== typeof this.secondsLeftInterval ) {
			clearInterval( this.secondsLeftInterval );
		}
		if( 'undefined' !== typeof this.recordingSecondsInterval ) {
			clearInterval( this.recordingSecondsInterval );
		}

		delete this.secondsLeft;
		delete this.recordingSeconds;
		delete this.secondsLeftInterval;
		delete this.recordingSecondsInterval;

		$("#cleftn").html("3");
		$("#count").html("0:00");
		$("#record_form").hide();
		$("div#post").show();
		unfinishedAudio('stop');
	},

	/**
	* starts a countdown of 3 seconds BEFORE
	* to start recording
	**/
	counterLeft: function() {
		// static variable
		if( 'undefined' == typeof this.secondsLeft ) {
			this.secondsLeft = 3;
		}

		if( 'undefined' == typeof this.secondsLeftInterval ) {
			this.secondsLeftInterval = setInterval(
					this.counterLeft.bind(this),
					1000
				);
			return;
		}

		if( this.secondsLeft > 0 ) {
			this.secondsLeft -= 1;
			$("#cleftn").text( String(this.secondsLeft) );
			return;
		}
		if( 0 === this.secondsLeft ) {
			// initialize everything
			clearInterval(this.secondsLeftInterval);
			delete this.secondsLeft;
			delete this.secondsLeftInterval;
			$("#cleft").hide();
			window.record.recorder.record(); // starts recording!
			this.isRecording = true;
			
			this.updateSeconds();
		}
	},
	/**
	* updates the seconds while recording
	* 'maxDuration' is declared in templates/footer.phtml
	**/
	updateSeconds: function() {
		if( 'undefined' === typeof this.recordingSecondsInterval ) {
			this.recordingSecondsInterval = setInterval(
				this.updateSeconds.bind(this),
				1000
			);
			this.recordingSeconds = 0;
			return;
		}
		// start increasing
		if( this.recordingSeconds < maxDuration ) {
			this.recordingSeconds += 1;

			var result, firstNumber, secondNumber;

			if( this.recordingSeconds < 60 ){
				firstNumber  = 0;
				secondNumber = this.recordingSeconds;
			}else{
				firstNumber  = Math.floor(this.recordingSeconds / 60 );
				secondNumber = this.recordingSeconds - (firstNumber*60);
			}
			if( String(secondNumber).length === 1 )
				secondNumber = "0" + String(secondNumber);

			result = String( firstNumber ) + ':' + String(secondNumber);

			$("#count").text(result);
			return;
		}

		if( maxDuration === this.recordingSeconds ) {
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