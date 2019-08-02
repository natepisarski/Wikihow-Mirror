(function($, mw) {

function setClickHandler() {
	$(document).on('click', '.thumbbutton', function (c) {
		c.preventDefault();
		// only allow one click
		$(this).unbind('click');

		// for usage logs tracking
		if ($('body').data('event_type') !== undefined) {
			WH.usageLogs.log({event_action: 'thumbs_up'});
		}

		var thumbsurl = $(this).siblings('#thumbUp').html().replace(/\&amp;/g,'&');

		$('#thumbsup-status').html(wfMsg('rcpatrol_thumb_msg_pending')).fadeIn('slow', function(n){});
		$.get(thumbsurl, {}, function(html){
			$('#thumbsup-status').css('background-color','#CFC').html(wfMsg('rcpatrol_thumb_msg_complete'));
			$(c.target).addClass("clicked");
		});
	});

}

mw.loader.using('ext.wikihow.UsageLogs', setClickHandler);

})(jQuery, mw);
