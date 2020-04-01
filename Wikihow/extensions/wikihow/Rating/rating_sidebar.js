(function($) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.RatingSidebar = {
		init: function() {
			// if it has special class hidden the we do not show this ever
			// this is added conditionally on the server side
			// it used to just not have this element in the page but we might need it
			if ( $('#ratearticle_sidebar').hasClass("hidden") ) {
				return;
			}
			//hidden by default in the template so it's all styled when it shows up
			$('#ratearticle_sidebar').show();
			$('.ra_side_vote').click(function() {
				WH.RatingSidebar.clickHandler(this);
			});
		},

		clickHandler: function(obj) {
			var rating = $(obj).data('vote');
			var pageId = mw.config.get('wgArticleId');
			WH.ratings.rateItem(rating, pageId, 'article_mh_style', 'sidebar');
		},

		showResult: function(vote) {
			var res_hdr = vote ? mw.msg('ras_res_yes_hdr') : mw.msg('ras_res_no_hdr');
			$('#ratearticle_sidebar h3').html(res_hdr);
			$('#ra_side_choices').hide();

			if (vote) {
				//yes!
				$('#ra_side_response_yes').show();

				$('#ras_response').click(function() {
					mw.loader.using('ext.wikihow.UserReviewForm', function () {
						var urf = new window.WH.UserReviewForm();
						urf.loadUserReviewForm();
					});
					return false;
				});
			}
			else {
				//no!
				$('#ra_side_response_no').show();
				$('#ra_side_response_no_form').show();
			}
		},

		disappear: function() {
			$('#ratearticle_sidebar').fadeOut();
		}
	}

	WH.RatingSidebar.init();
}($));
