(function($, mw) {
	'use strict';
	window.WH = window.WH || {};
	window.WH.ArticleTopMessage = {

		//needs to match ArticleTopMessage.class.php cookie name
		cookieName: '_atm_c19',

		init: function() {
			$('#atm_more').click($.proxy(function() {
				this.showAll();
				WH.event('all_banner_covid_click_expand_em');
				return false;
			},this));

			$('#atm_close').click($.proxy(function() {
				this.closeUp();
				WH.event('all_banner_covid_click_close_em');
				return false;
			},this));
		},

		showAll: function() {
			$('#atm_more').hide();
			$('#atm_the_rest').css('display', 'inline');
			$('#atm_body').css('font-size', '1em');
		},

		closeUp: function() {
			this.cookieTime();
			$('#article_top_message').slideUp();
		},

		cookieTime: function() {
			var expiredays = 60*60*24*7; //7 days
			mw.cookie.set(this.cookieName, '1',{expires: expiredays});
		}

	};

	$(document).ready(function() {
		WH.ArticleTopMessage.init();
	});

})(jQuery, mw);
