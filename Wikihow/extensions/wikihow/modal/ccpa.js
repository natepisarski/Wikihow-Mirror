(function ($) {
	'use strict';

	window.WH = WH || {};

	window.WH.CCPA = {
		popModal: function () {
			$.get('/Special:BuildWikihowModal?modal=ccpa', function(data) {
				$.modal(data, {
					zIndex: 100000007,
					maxWidth: 360,
					minWidth: 360,
					overlayCss: { "background-color": "#000" }

				});

				$('.ccpa_close').click(function () {
					$.modal.close()
					return false;
				});
				$('.wh_modal_btn_opt_out').click(function () {
					// log event
					 WH.maEvent("ccpa_opt_out", {origin: location.hostname, article_id: mw.config.get('wgArticleId')}, false);
					// save the cookie
					var date = new Date();
					date.setTime(date.getTime()+(365*24*60*60*1000));
					var expires = "; expires="+date.toGMTString();
					document.cookie = "ccpa_out=1"+expires+"; path=/";
					var url = window.location.href.split('?')[0]
					window.location.href = url;
					return false;
				});


			});
		},
		popOptedOutModal: function () {
			$.get('/Special:BuildWikihowModal?modal=ccpa_optedout', function(data) {
				$.modal(data, {
					zIndex: 100000007,
					maxWidth: 360,
					minWidth: 360,
					overlayCss: { "background-color": "#000" }
				});

				$('.wh_modal_btn_ok').click(function () {
					$.modal.close()
					return false;
				});
			});
		},

	};
}(jQuery));

