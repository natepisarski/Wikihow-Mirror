(function($) {
	'use strict';
	window.WH = window.WH || {};
	window.WH.SocialFooter = {
		addHandlers: function() {
			$('#social_footer .sf_icon').click(function() {
				var event = $(this).attr('id')+'_click';
				WH.maEvent(event, {category:'social_footer'});
			});
		}
	}

	WH.SocialFooter.addHandlers();
})(jQuery);