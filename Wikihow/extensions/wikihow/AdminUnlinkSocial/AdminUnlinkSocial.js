(function(window, document, $) {
	'use strict';

	/**
	 * Object returned by a successful API search
	 */
	var searchResult = null;

	$(document).ready(function() {
		$('#user-search-form').submit(function() {
			search();
			return false;
		});

		$('#google-unlink-link').click(function() {
			unlink(searchResult.wikiHowId, 'Google');
		});

		$('#facebook-unlink-link').click(function() {
			unlink(searchResult.wikiHowId, 'Facebook');
		});
	});

	// Search social login details by username
	var search = function() {
		$.ajax({
			type: 'POST',
			dataType: 'json',
			url: '/Special:AdminUnlinkSocial?action=getUser',
			data: { 'username': $('#user-search-username').val() },
			success: renderSuccess,
			error: renderError
		});
	};

	// Remove social login details from a WikiHow account
	var unlink = function(wikiHowId, type) {
		$.ajax({
			type: 'POST',
			dataType: 'json',
			url: '/Special:AdminUnlinkSocial?action=unlink' + type,
			data: { 'wikiHowId': wikiHowId },
			success: search,
			error: renderError
		});
	};

	// Display user social login details
	var renderSuccess = function(data) {
		$('#error-box').hide();
		$('#search-results').show();

		$('#wikihow-name').text(data.wikiHowName);
		$('#wikihow-id').text(data.wikiHowId ? data.wikiHowId : 'Not found');
		$('#google-id').text(data.googleId ? data.googleId : 'Not found');
		$('#facebook-id').text(data.facebookId ? data.facebookId : 'Not found');

		searchResult = data;

		if (data.googleId) {
			$('#google-unlink-div').show();
		} else {
			$('#google-unlink-div').hide();
		}

		if (data.facebookId) {
			$('#facebook-unlink-div').show();
		} else {
			$('#facebook-unlink-div').hide();
		}
	};

	// Display API error
	var renderError = function(jqXHR) {
		var obj = JSON.parse(jqXHR.responseText);
		$('#search-results').hide();
		$('#error-msg').text(obj.error);
		$('#error-box').show();
	};

}(window, document, jQuery));
