$(document).ready(function () {
	$('input[name="recurring"]').change(function () {
		if ($(this).val() == '2') {
			$('div#recurring_details').slideDown();
			$('input#interval').addClass('required');
		}
		else {
			$('div#recurring_details').slideUp();
			$('div#recurring_details input').removeClass('required');
		}
	});

	$('input[name="recurring_end"]').change(function () {
		if ($(this).val() == 'occurrences') {
			$('input#occurrences').addClass('required');
		}
		else {
			$('input#occurrences').removeClass('required');
		}
	});
	
	$('select#customer_id').change(function () {	
		if ($(this).val() != '') {
			$('div#transaction_customer_details').slideUp();
		}
		else {
			$('div#transaction_customer_details').slideDown();
		}
	});
	
	if ($('p.no_gateway').length != 0) {
		$('#form_transaction input, #form_transaction select').attr('disabled','disabled');
	}
	
	$('select[name="gateway"]').change(function () {
		$('[name="gateway_type"][value="specify"]').attr('checked',true);
	});
	
	// do we require the CC fields?
	require_cc_fields();
	
	$('select[name="gateway"]').click(function () {
		require_cc_fields();
	});
});

function require_cc_fields () {
	$('select[name="gateway"]').each(function () {
		if ($(this).children(':selected').hasClass('no_credit_card')) {
			$('#cc_number').removeClass('required');
			$('#cc_name').removeClass('required');
		}
		else {
			$('#cc_number').addClass('required');
			$('#cc_name').addClass('required');
		}
	});
}