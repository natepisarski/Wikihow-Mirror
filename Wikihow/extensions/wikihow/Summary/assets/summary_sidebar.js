(function($) {
	'use strict';
	window.WH = window.WH || {};
	window.WH.SummarySidebar = {
		init: function() {
			$('.summary_review_show').on('click', function() {
				$(this).hide()
					.parent().find('.summary_review_more').show()
					.parent().find('.summary_ellipsis').hide();

				WH.maEvent('rr-qs-read-more-click');
				return false;
			});
		}
	}

	WH.SummarySidebar.init();
})(jQuery);