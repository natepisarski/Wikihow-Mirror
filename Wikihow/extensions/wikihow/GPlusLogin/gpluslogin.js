//chose not to use the default username
$(document).on('click', '#gpl_x', function() {
	$('#gpl_faux_username').hide();
	$('#gpl_requested_username').show();
	$('input[name="email"]').removeAttr('readonly').removeClass('gpl_readonly');
});

$(document).on('submit', '#gpl_form', function(e) {
	var isFaux = $('#gpl_faux_username').is(':visible');
	if(!isFaux && !$('input[name="requested_username"]').val()) {
		e.preventDefault();
		$('#gpl_error').html('Please enter a wikiHow username').show();
		return false;
	}
	else {
		WH.maEvent('account_signup', { category: 'account_signup', type: 'google' }, false);
		return true;
	}
});


