(function(window, document, $) {
	'use strict';

	$(document).ready(function() {

		$('#tr_trends_btn').click(function() {
			downloadTrends();
		});

		$('#tr_report_btn').click(function() {
			downloadSearchResults();
		});

	});

	function downloadTrends() {
		$.download(window.location.href, {'action': 'trends'});
	};

	function downloadSearchResults() {
		var $textArea = $('#tr_queries_textarea');
		if (!$textArea.val()) {
			alert("Nothing to download (the text area is empty)");
		} else {
			$.download(window.location.href, {'action': 'search', 'queries': $textArea.val()});
		}
	};

}(window, document, jQuery));
