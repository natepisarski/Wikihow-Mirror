
var BUBBLE_TIMEOUT = 5;
$("document").ready(function() {
	window.setTimeout(bubbleFeedItem, BUBBLE_TIMEOUT * 1000);
	}
);

function bubbleFeedItem() {
	var last = $("#feeditems .feeditem").last();
	last.fadeOut(400, function() { 
			last.remove();
			$("#feeditems").prepend(last);
			last.fadeIn();
			});
	window.setTimeout(bubbleFeedItem, BUBBLE_TIMEOUT * 1000);
}

