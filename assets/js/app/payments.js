/**
* Javascript for the payments
* I promise I will fix all the inconsistencies
* of this file in less than a week
*
**/

/**
* Callback after Stripe generated the token
* @return void
**/

function stripeTokenResponse( status, response ) {
	if( response.error ) {
		displayError(response.error.message);
		$('#premium-submit').removeAttr('disabled');
		return;
	}
	var params = {
		method: 'card',
		token: response.id
	};
	// submit the token
	$.ajax({
		type: "POST",
		url: ajaxUrl + "post/charge",
		data: params,
		cache: false,
		error: function() {
			displayError(
				'We apologize we had an internal error. ' +
				'Please, try again later.'
			);
		},
		success: function( result ) {
			result = JSON.parse(result);
			if( ! result.success ) {
				displayError( result.response );
				$('#premium-submit').removeAttr('disabled');
				return;
			}
			displayInfo(result.response);
			// this is a global var
			maxDuration = 300;
			$('#post-max-minutes').text('5');
			// say goodbye to the #premium-get tab
			$("#premium-selector-get")
				.removeClass('active')
				.addClass('disabled')
				.find('a') // the a inside has the color
				.addClass('text-lighten-4'); // change the color c:
			// now add that to the premium-enjoy
			$("#premium-selector-enjoy")
				.removeClass('disabled')
				.addClass('active')
				.find('a')
				.removeClass('text-lighten-4');
			// transform the premium_until UNIX timestamp
			// to something legible
			var date = new Date( parseInt(result.premium_until) * 1000 );
			$("#premium-until").text(
					date.getDate()     + '/' +
					date.getMonth()    + '/' +
					date.getFullYear()
				);
			// switch to the '#premium-enjoy' tab
			$('ul.tabs').tabs('select_tab', 'premium-enjoy');
		}
	});
}
/**
* Luhn algoritm to validate the card
* Adapted from https://gist.github.com/DiegoSalazar/4075533
* @return bool
**/
function isCardValid( card ) {
  // accept only digits, dashes or spaces
	if (/[^0-9-\s]+/.test(card) ) {
		return false;
	}
	// The Luhn Algorithm. It's so pretty.
	var nCheck = 0;
	var nDigit = 0;
	var bEven  = false;
	card       = card.replace(/\D/g, "");

	for( var n = card.length - 1; n >= 0; n-- ) {
		var cDigit = card.charAt(n);
		nDigit     = parseInt(cDigit, 10);

		if( bEven ) {
			if( (nDigit *= 2) > 9) {
				nDigit -= 9;
			}
		}

		nCheck += nDigit;
		bEven   = !bEven;
	}

	return 0 === (nCheck % 10);
}

/**
* Prepares the form 
*
**/
$('#form-premium').on('submit', function(event) {
	$('#submit-premium').attr('disabled', 'disabled');
	// Request a token from Stripe:
	Stripe.card.createToken($(this), stripeTokenResponse);
	// Prevent the form from being submitted:
	return false;
});

/**
* validates the card number
**/
$("#premium-input-card").on('change', function() {
	var card = $(this).val();
	if( isCardValid(card) ) {
		$(this)[0].setCustomValidity('');
		$(this).removeClass('invalid').addClass('valid');
	} else {
		$(this)[0].setCustomValidity(' ');
		$(this).removeClass('valid').addClass('invalid');
	}
});
/**
* validates the exp month
**/
$("#premium-input-exp").on('change', function() {
	var exp  = $(this).val();
	exp      = $.trim(exp);
								// replace any other way
	exp      = exp
				.split(' ').join('') // no spaces
				.split('-').join('/') // no dashes
				.split('\\').join('/'); // no backslashes

	isValid  = function( exp ) {
		if( ! /^([0-9]{1,2})\/([0-9]{2,4})$/.test(exp) ) {
			return false;
		}
		var monthYear  = exp.split('/');
		var month      = monthYear[0];
		var year       = monthYear[1];
		month          = parseInt(month);
		if( month > 12 || 0 === month ) {
			// oh rly?
			return false;
		}
		// month is valid, check year
		if( year.length === 2 ) {
			year = '20' + year;
		}
		var date        = new Date();
		var currentYear = date.getFullYear();
		year            = parseInt(year);
		if( year < currentYear ) {
			return false;
		}
		if( year > currentYear ) {
			return true;
		}
		// year is the current year, then check the month c:
		var currentMonth = date.getMonth() + 1;
		if( month < currentMonth ) {
			return false;
		}
		return true;
	};

	if( isValid(exp) ) {
		$(this)[0].setCustomValidity('');
		$(this).removeClass('invalid').addClass('valid');
	} else {
		$(this)[0].setCustomValidity(' ');
		$(this).removeClass('valid').addClass('invalid');
	}
});

/**
* validates the card security code
**/
$("#premium-input-cvc").on('change', function() {
	var cvc = $(this).val();
		cvc = $.trim(cvc);
	if( /^[0-9]{3,4}/.test(cvc) ) {
		$(this)[0].setCustomValidity('');
		$(this).removeClass('invalid').addClass('valid');
	} else {
		$(this)[0].setCustomValidity(' ');
		$(this).removeClass('valid').addClass('invalid');
	}
});

/**
* starts recording after clicking on
* "start recording" from the premium modal
**/
$("#premium-action-record").on('click', function() {
	$('#payments').closeModal();
	$("#post-record").trigger('click');
});