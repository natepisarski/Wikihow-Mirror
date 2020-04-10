(function($, mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.SubmitATip = {

		url: '/Special:TipsAndWarnings',
		clicked: [],

		init: function() {
			$(document).on('click', '.addtip', function(e) {
				e.preventDefault();

				$(this).hide();
				$('.tip_waiting').show();

				var newTip = $.trim($(this).parent().find('.newtip').val());

				if(newTip != "") {

					var data = {'aid' : wgArticleId, 'tip' : newTip};
					$.get(WH.SubmitATip.url, data, function(data){

						//replace with a thank you message
						$('.addTipForm').hide();
						$('.addTipThanks').show();

					}, "json");
				}
			});
		}
	}

	$(document).ready(function() {
		WH.SubmitATip.init();
	});

})(jQuery, mw);
