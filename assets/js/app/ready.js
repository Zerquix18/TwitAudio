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

	if( ! window.record.can_record() ) {
		$("#or, #record").hide();
		$("#upload").css('float', 'none');
		var value = read_cookie('no_record_support');
		if( null !== value && '' !== value )
			return;
		$("#norecordsupport").show();
		document.cookie = 'no_record_support=1';
	}

	$.each( window.onload_functions, function( key, value ) {
		value.call(null);
	});
	
});