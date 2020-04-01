(function($) {
	'use strict';
	window.WH = window.WH || {};
	window.WH.SocialFooter = {
		addHandlers: function() {
			$('#sf .sf_icon').click(function() {
				var event = $(this).attr('id')+'_click';
				WH.maEvent(event, {category:'social_footer'});
			});
		}
	};

	WH.SocialFooter.addHandlers();
})(jQuery);