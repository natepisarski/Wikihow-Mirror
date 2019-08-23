(function($, mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.ImageFeedbackAdmin = {
		init: function() {
			$(document).on('click', '#if_submit', function (e) {
				if ($('#if_urls').val().length) {
					e.preventDefault();
					var data = {'if_urls' : $('#if_urls').val(), 'a' : 'reset_urls'};
					$.post("/" + mw.config.get('wgPageName'), data, function(res) {
						$('#if_result').html(res);
					});
				}
			});
		}

	};

	WH.ImageFeedbackAdmin.init();
}($, mw));
