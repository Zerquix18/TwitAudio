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
 * if not, it will upload the 'upload-file' input
 *
 * @param {Object} options
 * 
**/
window.uploadAudio = function(options) {
    var isVoice = options.isVoice || false;
    $("#record-box").hide();
    $("#post-box")  .hide();
    window.progressiveText.start(
      '#uploading-text',
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
    $("#uploading-box").show();
    var uploadForm = {
      beforeSend: function() {
        $("#player-cut").jPlayer("destroy");
        window.effects.clean();
      },
      error: function(xhr) {
        displayError(
          'There was an error while uploading your audio. ' + 
          'Please check your Internet connection'
        );
        $("#uploading-box").hide();
        $("#uploading-progress").width(0);
        $("#post-box").show();
        window.progressiveText.stop('#uploading-text');
        $("#upload-form").trigger('reset');
      },
      uploadProgress: function(event, position, total, percent) {
        $("#uploading-progress").animate({
          width: percent + '%'
        });
      },
      complete: function(xhr) {
        window.progressiveText.stop('#uploading-text');
        $("#upload-form").trigger('reset');
        $("#uploading-progress").width("100%");

        var result  = JSON.parse(xhr.responseText);
        var tmpUrl  = result.tmp_url || '';
        var id      = result.id      || '';
        var effects = result.effects || '';

        if (!result.success) {
          if (!tmpUrl) {
            // there was an error
            // and it's not because it's too long
            $("#uploading-box").hide();
            $("#uploading-progress").width(0);
            $("#post-box").show();
            displayError(result.response);
            return;
          }
          // needs cut
          unfinishedAudio('start');
          window.tmpUrl = tmpUrl;
          // make it global
          $("#player-cut").jPlayer({
            ready: function(event) {
              $(this).jPlayer("setMedia", {
                // so this guy catches it
                mp3: window.tmpUrl,
              });
            },
            cssSelectorAncestor: '#container-cut',
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
          $("#uploading-box").hide();
          $("#uploading-progress").width(0);
          $("#cut-form").show();
          $("#cut-audio_id").val(result.id);
          return;
        }
        $(".effect-original").data('url', tmpUrl);

        unfinishedAudio('start');

        window.effects.load(result.id);
        window.effects.showLoading(result.effects);
        window.preparePostForm(result.id, result.tmp_url);
      }
    };
    if (isVoice && window.record.recorder) {
      /**
      * If it's voice then we'll upload the
      * base64 as 'bin'
      **/
      window.record.recorder.exportMP3(function(blob) {
        var reader    = new FileReader();
        reader.onload = function(event) {
          var _fileReader      = {};
          _fileReader.is_voice = '1';
          _fileReader.bin      =  event.target.result;
          uploadForm.data      = _fileReader;
          $("#upload-form").ajaxSubmit(uploadForm);
        };
        reader.readAsDataURL(blob);
      });
      return;
    }
    uploadForm.data = { is_voice: '0' };
    $("#upload-form").ajaxSubmit(uploadForm);
};

/**
 * Will execute when the user clicks on the upload icon
**/

$("#post-upload").on('click', function() {
  $("#upload-file").trigger('click');
  $(this).blur();
});

/**
 * Will execute when the user tries to upload an audio
**/

$("#upload-file").on('change', function() {

  var format   = $(this).val().split('.');
  var fileSize = this.files[0].size / 1024 / 1024;

  format = format[ format.length - 1 ];
  format = format.toLowerCase();

  if (!inArray(format, ['mp3', 'ogg'] )) {
    return displayError('Format not allowed');
  }

  /*
  * uploadFileLimit is defined in templates/footer.phtml
  */ 
  if (fileSize > uploadFileLimit) {
    return displayError(
      'The file size is greater than your current ' +
      'limit \'s, ' + uploadFileLimit + ' mb');
  }

  window.uploadAudio({ isVoice: false });
});

/************************* CUT *************************/

/**
 * Will execute when the user submits
 * The cut form
**/

$("#cut-form").ajaxForm({
  beforeSend: function() {
    $("#player-preview").jPlayer('destroy');
    $("#cut-form").hide();
    $("#uploading-progress")
      .removeClass('determinate')
      .addClass('indeterminate');

    window.progressiveText.start(
      '#uploading-text',
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
    $("#uploading-box").show();
    $.jPlayer.pause();
    window.effects.clean();
  },
  error: function() {
    progressiveText.stop('#uploading-text');
    displayError(
      'There was a problem while cutting your audio. Please check your Internet connection',
      10000
    );
    // get everything back
    $("#uploading-box").hide();
    $("#uploading-progress")
      .removeClass('indeterminate')
      .addClass('determinate');
    $("#cut-form").show();
  },
  complete: function(xhr) {
    var result = JSON.parse(xhr.responseText);
    var tmpUrl = result.tmp_url;
    var id     = result.id;

    progressiveText.stop('#uploading-text');
    $("#uploading-progress")
      .removeClass('indeterminate')
      .addClass('determinate');

    if (!result.success) {
      displayError(result.response);
      $("#uploading-box").hide();
      $("#cut-form").show();
      return;
    }

    $("#cut-form").trigger('reset');
    $(".original").data('url', tmpUrl);

    effects.load(id);
    effects.showLoading(result.effects);

    window.preparePostForm(id, tmpUrl);
  }
});

/**
 * Will validate the #cut-form inputs
**/

$("#cut-end, #cut-start").on('keyup', function() {

  var numbers;
  var diff;
  var btn       = $("#cut-button");
  var start     = $("#start").val();
  var end       = $("#end")  .val();
  var isNumeric = function(value) {
      return /^[0-9]{0,3}$/.test(value);
    };

  if (!isNumeric(start)) {

    if (!/^([0-9]{1,2}):([0-9]{1,2})$/.test(start)) {
      return btn.attr('disabled', 'disabled');
    }

    numbers = start.split(':');
    start   = (parseInt(numbers[0]) * 60) + parseInt(numbers[1]);
  } else {
    start   = parseInt(start);
  }

  if (!isNumeric(end)) {

    if (!/^([0-9]{1,2}):([0-9]{1,2})$/.test(end)) {
      return btn.attr('disabled', 'disabled');
    }

    numbers = end.split(':');
    end     = (parseInt(numbers[0]) * 60) + parseInt(numbers[1]);
  } else {
    end     = parseInt(end);
  }

  diff = end-start;
  /* maxDuration is declared in templates/footer.phtml */
  if ((start >= end) || diff > maxDuration || diff < 1) {
    return btn.attr('disabled', 'disabled');
  }

  return btn.removeAttr('disabled');
});

$("#cut-cancel, #post-cancel").on('click', function() {
  if (true !== confirm('Are you sure?')) {
    return false;
  }
  $("#cut-form, #post-form").hide();
  $("#post-box").show();
  $.jPlayer.pause();
  unfinishedAudio('stop');
});

/************************* POST *************************/

/**
 * Prepares the the Post Form :O
 * @param  {string} id     The temporary ID of the audio
 * @param  {string} tmpUrl The temporary URL of the audio
 */
window.preparePostForm = function(id, tmpUrl) {

  $("#post-audio_id").val(id);

  window.tmpPostPreview = tmpUrl;
  // made it global

  $("#player-preview").jPlayer({
    ready: function(event) {
      $(this).jPlayer("setMedia", {
        mp3: window.tmpPostPreview,
        // so this guys catches it
      });
    },
    cssSelectorAncestor: '#container-preview',
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

  $("#uploading-box").hide();
  $("#uploading-progress").width(0);
  $("#post-form").show();
};

$("#post-form").ajaxForm({
  beforeSend: function() {
    $.jPlayer.pause();
  },
  error: function() {
    displayError(
      'Unable to post. Please check your Internet connection.'
    );
  },
  complete: function(xhr) {
    $("#uploading-progress").width(0);
    var result = JSON.parse(xhr.responseText);
    if (!result.success) {
      return displayError(result.response);
    }

    $("#post-input-description").val("");
    $("#post-audio_effect").val('original');
    $("#uploading-box, #post-form").hide();
    $("#post-box").show();
    unfinishedAudio('stop');
    return displayInfo(result.response);
  },
});

/************************* REPLIES *************************/

$("#reply-form").ajaxForm({
  beforeSend: function() {
    $("#reply-input, #reply-submit")
      .attr('disabled', 'disabled');
  },
  error: function() {
    displayError('There was an error while adding your reply.');
    $("#reply-input, #reply-submit").removeAttr('disabled');
  },
  complete: function(xhr) {
    $("#reply-input, #reply-submit").removeAttr('disabled');
    var result = xhr.responseText;
    result     = JSON.parse(result);
    if (!result.success) {
      return displayError(result.response);
    }

    $("#reply-input").val('');
    $("#reply-input-label").removeClass('active');
    // remove the 'there are not...' message
    $("#replies div.alert").remove();

    // add the result
    $("#replies").prepend(result.response);
    updateDates();
  }
});

$("#reply-input").on('keyup keydown', function(e) {
  var value = $(this).val();

  if ($.trim(value).length > 0) {
    $("#reply-submit").removeAttr('disabled');
  } else {
    $("#reply-submit").attr('disabled', 'disabled');
  }

});

/************************* SETTINGS *************************/

$("#settings-form").ajaxForm({
  beforeSend: function() {
    $("#settings-form button").attr('disabled', 'disabled');
  },
  error: function() {
    $("#settings-form button").removeAttr('disabled');
    displayError(
      'Could not update your settings.' + 
      'Please check your Internet connection.'
    );
  },
  complete: function(xhr) {
    $("#settings-form button").removeAttr('disabled');

    var result = JSON.parse(xhr.responseText);
    if (result.success) {
      return displayInfo(result.response);
    }

    displayError(result.response);
  }
});

$("#search-type").on('change', function() {
  if ($(this).val() == 'a') {
    $("#search-sort").removeAttr('disabled');
    $('#search-sort').material_select();
  } else {
    $('#search-sort').material_select('destroy');
    $("#search-sort").attr('disabled', 'disabled');
  }
});
