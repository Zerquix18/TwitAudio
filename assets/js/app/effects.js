/**
 * Loads the effects and apply them
 *
 * @author Zerquix18
 * @copyright 2016 Luis A. Martínez
**/
window.effects = {

  allEffectsLoaded: true,

  loadInterval: 0,

  loadedEffects: [],

  loadUrlHelper: -1,

  loadedUrls: [],

  /**
   * Loads the effects for a temporary audio
   * @param  {String} audioId The temporary ID of the audio
   */
  load: function(audioId) {
    if (!this.allEffectsLoaded) {
      return;
    }
    if (0 === this.loadInterval) {
      this.loadInterval = setInterval(
          this.load.bind(this),
          3000,
          audioId
        );
      return; // wait the 3 seconds...
    }

    var params = { 'id': audioId };
    $.ajax({
      type: "GET",
      cache: false,
      url: ajaxUrl + 'get/checkeffects',
      data: params,
      success: function(result) {
        result = JSON.parse(result);
        
        if (!result.success) {
          clearInterval(window.effects.loadInterval);
          return displayError(result.response);
        }

        /* fun starts here */
        /** load all the audios with effects **/
        var loadedEffects = result.loaded_effects;
        var areAllLoaded  = result.are_all_loaded;

        for (var i = 0; i < loadedEffects.length; i++) {
          // 2 keys: file for the path,
          // and name for the effect name
          var name = loadedEffects[i].name;
          var file = loadedEffects[i].file;

          if (inArray(name, window.effects.loadedEffects)) {
            continue;
          } else {
            window.effects.loadedEffects.push(name);
          }

          window.effects.loadedUrls.push(file);

          $(".effect-action-choose.effect-" + name)
            .attr('data-url', file);

          $("#player-effect-" + name).jPlayer({
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

            cssSelectorAncestor : '#container-effect-' + name,
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
          $("#effect-" + name + " > .effect-loading").hide();
          $("#effect-" + name + " > .effect-preview").show();
        }
        if (areAllLoaded) {
          console.log(result.are_all_loaded);
          console.log('all loaded!');
          clearInterval(window.effects.loadInterval);
          window.effects.allEffectsLoaded  = true;
          window.effects.loadedEffects     = [];
          window.effects.loadUrlHelper     = -1;
        }
      }
    });
  },
  /**
   * Prepares all the effects before they are loaded
   * So the page shows that they are loading
   * @param  {Object} effects The effects to load.
   */
  showLoading: function(effects) {
    /**
    * We have now an array
    * each key is an object
    * with name and name_public
    **/
    for (var i = 0; i < effects.length; i++) {
      var effectName        = effects[i].name;
      var effectNamePublic  = effects[i].name_public;
      var effectSelector    = 'effect-' + effects[i].name;

      $("#effect-none")
        .clone()
        .attr('id', effectSelector)
        .appendTo('#effects > .modal-content');

      // change the title
      $("#" + effectSelector + " h5")
        .text(effectNamePublic);

      // now the atts
      $("#" + effectSelector + ' .effect-preview .jp-jplayer')
        .attr('id', 'player-effect-' + effectName);

      $("#" + effectSelector + ' .jp-audio')
        .attr('id', 'container-effect-' + effectName);

      // the choose button
      $("#" + effectSelector + ' .effect-preview button')
        .attr('data-choose', effectName)
        .removeClass('effect-none').addClass(effectSelector)
        .on('click', this.effectChooseListener);

        // now it is ready to be loaded by `this.load` :)

      $("#" + effectSelector).show();
    }
    // finally, add listener to the original button
    $('.effect-original').on('click', this.effectChooseListener);
  },
  /**
   * Cleans all the temporary effects after the audio is canceled
   * Or uploaded.
   */
  clean: function() {
    // arrivederchi!
    $(".effect-preview-box").not('#effect-none').remove();
    // in case they all did not load
    clearInterval(window.effects.loadInterval);
  },

  /**
   * The listener to be executed when someone clicks '.effect-action-choose'
   */
  effectChooseListener: function() {

    if (undefined === $(this).data('url')) {
      // no urls loaded
      return;
    }
    // in case any player is still playing
    $.jPlayer.pause();
    $("#player-preview").jPlayer('setMedia', {
        'mp3' : $(this).data('url')
      });
    $("#effects").closeModal();
    $("#post-audio_effect").val( $(this).data('choose') );

    if ('original' === $(this).data('choose')) {
      displayInfo('OK. Got it.');
    } else {
      displayInfo('Effect added!');
    }
  }
};
