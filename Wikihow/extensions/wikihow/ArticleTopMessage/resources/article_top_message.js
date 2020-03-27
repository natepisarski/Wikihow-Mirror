(function($, mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.ArticleTopMessage = {

		//needs to match ArticleTopMessage.class.php cookie name
		cookieName: '_atm_c19',

		init: function() {
			$('#atm_more').click(function() {
				$('#atm_the_rest').css('display', 'inline');
				$(this).hide();
				return false;
			});

			$('#atm_close').click($.proxy(function() {
				this.closeUp();
				return false;
			},this));
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