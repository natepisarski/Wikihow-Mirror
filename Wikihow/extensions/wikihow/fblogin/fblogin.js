$('#fbc_x').live('click', function() {
	$('#fbc_faux_username').hide();
	$('#fbc_requested_username').css('display', 'inline-block').css('visibility', 'visible');
	$('input[name="email"]').removeAttr('readonly').removeClass('fbc_readonly');
});

$('#fbc_form').live('submit', function(e) {
	var isFaux = $('#fbc_faux_username').is(':visible');
	if(!isFaux && !$('input[name="requested_username"]').val()) {
		e.preventDefault();
		$('#fbc_error').html('Please enter a wikiHow username');
		return false;
	}
	else {
		WH.maEvent('account_signup', { category: 'account_signup', type: 'facebook' }, false);
		return true;
	}
});
