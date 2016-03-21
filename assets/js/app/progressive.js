/**
*
* Shows multiples text
* progressively
*
* @author Zerquix18
*
**/
window.progressive_text = {

	total_progressives: [],

	start: function( selector, texts, interval ) {
		
		interval = interval || 5;
		this.total_progressives[ selector ] = [];
		var mili_seconds,
			change_text = function( selector, text ) {
				$(selector).text(text);
			};

		for( var i = 0; i < texts.length; i++ ) {
			mili_seconds = interval * (i * 1000);
			var id = window.setTimeout(
				change_text,
				mili_seconds,
				selector,
				texts[i]
			);
			this.total_progressives[ selector ].push( id );
		}
	},

	stop: function( selector ) {

		if( ! in_array( selector, this.total_progressives ) )
			return false;

		for( var i = 0; i < this.total_progressives[ selector ].length; i++)
			window.clearTimeout( this.total_progressives[ selector ][i] );

	}

};