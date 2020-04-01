//chose not to use the default username
$(document).on('click', '#cl_x', function() {
	$('#cl_faux_username').hide();
	$('#cl_requested_username').show();
	$('input[name="email"]').removeAttr('readonly').removeClass('cl_readonly');
});

$(document).on('submit', '#cl_form', function(e) {
	var isFaux = $('#cl_faux_username').is(':visible');
	if(!isFaux && !$('input[name="requested_username"]').val()) {
		e.preventDefault();
		$('#cl_error').html('Please enter a wikiHow username').show();
		return false;
	}
	else {
		WH.maEvent('account_signup', { category: 'account_signup', type: 'civic' }, false);
		return true;
	}
});