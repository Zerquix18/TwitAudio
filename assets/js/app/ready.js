/**
* Executes the ready function
*
**/
$(document).ready( function() {

	$('.collapsible').collapsible();

	$('.button-collapse').sideNav({
		menuWidth: 240,
		edge: 'left',
		closeOnClick: false
	});

	$('select').material_select();

	$('.modal-trigger').leanModal({
		dismissible: true,
		opacity: 0.5,
		in_duration: 300,
		out_duration: 200,
	});
	
	// materialize tabs
	$('ul.tabs').tabs();

	if( ! window.record.canRecord() ) {
		$("#or, #record").hide();
		$("#upload").css('float', 'none');
		var value = readCookie('no_record_support');
		if( null !== value && '' !== value ) {
			return;
		}
		$("#norecordsupport").show();
		document.cookie = 'no_record_support=1';
	}

	$.each( window.onLoadFunctions, function( key, value ) {
		value.call(null);
	});
	
});