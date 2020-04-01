( function($, mw) {

$(document).ready(function() {
	showBalloonTip();
});

function showBalloonTip() {
	if (!$('#'+bubble_target_id).length) {
		console.log("warning...target element " + bubble_target_id + " does not exist");
		return;
	}

	$('#'+bubble_target_id).before($('.tip_bubble_outer'));

	if ($.cookie(cookieName) != '1') {
		window.setTimeout(function() {
			$('.tip_bubble_outer').fadeIn('slow');
			$('.tip_bubble_outer').on('click', '.tip_x', function(e) {
				$('.tip_bubble_outer').fadeOut('slow');
				$.cookie(cookieName, '1');
			});
		}, 2000);
	}
}

}(jQuery, mediaWiki) );
