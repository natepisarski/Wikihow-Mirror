(function($) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.ArticleReviewers = {

		init: function() {
			$('.ar_sidebar_item').click(function() {
				WH.ArticleReviewers.show($(this).data('anchor'));
				$('.ar_sidebar_item').removeClass('selected');
				$(this).addClass('selected');
			});
		},

		show: function(anchor) {
			if (anchor == 'all') {
				$('.ar_category').show();
				return;
			}

			var catname = '';
			$('.ar_category').each(function() {
				catname = $(this).find('.ar_anchor').attr('name');
				catname != anchor ? $(this).hide() : $(this).show();
			});
		}
	}

	WH.ArticleReviewers.init();
}($));
