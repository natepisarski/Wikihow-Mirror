WH.convertTalkPageDates = (function() {
	$('.de_date').each( function() {
		if ( $(this).attr('data-datestamp') ) {
			$(this).text($.format.prettyDate($(this).attr('data-datestamp')));
		}
	});
});


$(document).ready( function() {
	WH.convertTalkPageDates();
	setInterval(WH.convertTalkPageDates, 60000);
});
