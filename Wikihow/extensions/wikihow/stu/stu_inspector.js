(function($, mw) {

// Callback used by Stu when a new ping happens
function addPingStatement(line) {
	if (line.indexOf('class="replace_line"') === -1) {
		$('#stu_debug').append('<div class="stu_line">' + line + '</div>');

		// remove lines at the top to get back down to MAX_LINES
		var MAX_LINES = 10;
		var lines = $('#stu_debug .stu_line');
		if ( lines.length > MAX_LINES ) {
			var toRemove = lines.length - MAX_LINES;
			lines.filter(function (index) {
				return index < toRemove;
			})
			.remove();
		}
	} else {
		$('#stu_debug').html('<div class="stu_line">' + line + '</div>');
		$('#stu_debug').css({'height': '25px'});
	}
}

// Initialize the analytics debug display
function initialize() {
	$('body').append('<div id="stu_debug"></div>');
}

// tap into stu's data here and display it
$(document).ready( function() {
	initialize();
	WH.Stu.registerDebug(addPingStatement);
} );

})(jQuery, mediaWiki);
