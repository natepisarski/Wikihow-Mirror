(function($, mw) {

// Callback used by Stu when a new ping happens
function addPingStatement(url) {
	$('#stu_debug').append('<div class="stu_line">' + url + '</div>');
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
