(function($) {
	'use strict';
	window.WH = window.WH || {};
	window.WH.SocialFooter = {
		addHandlers: function() {
			$('#sf .sf_icon').click(function() {
				WH.event('all_footer_social_links_click_go_em', { type: $(this).attr('title') } );
			});
		}
	};

	WH.SocialFooter.addHandlers();
})(jQuery);
