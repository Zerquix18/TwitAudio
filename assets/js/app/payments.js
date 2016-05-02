/**
* Javascript for the payments
*
**/

$(document).ready( function() {
	var $form = $('#form-premium');
	$form.on('submit', function( event ) {
		$('#submit-premium').attr('disabled', 'disabled');
		// Request a token from Stripe:
		Stripe.card.createToken($form, stripe_token_response);
    	// Prevent the form from being submitted:
		return false;
	});
});

function stripe_token_response( status, response ) {
	if( response.error ) {
		display_error(response.error.message);
		$('#submit-premium').removeAttr('disabled');
		return;
	}
	var params = {
		method: 'card',
		token: response.id
	};
	// submit the token
	$.ajax({
		type: "POST",
		url: ajaxurl + "post/charge",
		data: params,
		error: function() {
			display_error(
				'We apologize we had an internal error. ' +
				'Please, try again later.'
			);
		},
		success: function( result ) {
			result = JSON.parse(result);
			if( ! result.status ) {
				display_error( result.response );
				$('#submit-premium').removeAttr('disabled');
				return;
			}
			display_info(result.response);
			
			// this is a global var
			max_duration = 300;
		}
	});
}