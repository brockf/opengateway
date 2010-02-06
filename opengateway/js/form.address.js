$(document).ready(function () {
	// place first and last name text
	if ($('input#first_name').val() == '' || $('input#first_name').val() == 'First Name') {
		$('input#first_name').val('First Name');
		$('input#first_name').addClass('emptyfield');
		NameChecker();
	}
	
	if ($('input#last_name').val() == '' || $('input#last_name').val() == 'Last Name') {
		$('input#last_name').val('Last Name');
		$('input#last_name').addClass('emptyfield');
		NameChecker();
	}
	
	// show State select when on United States/Canada
	$('select#state_select').hide();
	if ($('select#country').val() == 'US' || $('select#country').val() == 'CA') {
		$('select#state_select').show();
		$('input#state').hide();
	}
	$('select#country').change(function () {
		if ($(this).val() == 'US' || $(this).val() == 'CA') {
		 	$('select#state_select').show();
		 	$('input#state').hide();
	    } else {
	    	$('select#state_select').hide();
		 	$('input#state').show();
	    }
	});
});

function NameChecker () {
	$('input.emptyfield').focus(function () {
		$(this).val('');
		$(this).removeClass('emptyfield');
	});
}