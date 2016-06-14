/**
*
* Shows multiples text
* progressively
*
* @author Zerquix18
*
**/
window.progressiveText = {

  totalProgressives: [],

  start: function(selector, texts, interval) {
    interval = interval || 5;
    this.totalProgressives[ selector ] = [];
    var miliSeconds;
    var changeText = function(selector, text) {
        $(selector).text(text);
      };

    for (var i = 0; i < texts.length; i++) {
      miliSeconds = interval * (i * 1000);
      var id      = window.setTimeout(
                      changeText,
                      miliSeconds,
                      selector,
                      texts[i]
                    );
      this.totalProgressives[ selector ].push(id);
    }
  },
  stop: function( selector ) {
    if (!inArray( selector, this.totalProgressives )) {
      return false;
    }

    for (var i = 0; i < this.totalProgressives[ selector ].length; i++) {
      window.clearTimeout( this.totalProgressives[ selector ][i] );
    }
  }
};
