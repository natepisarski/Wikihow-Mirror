(function ($) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.CategoryExpertise2 = {

		popModal: function (cat) {
			var url = "/extensions/wikihow/common/jquery.simplemodal.1.4.4.min.js";
			$.getScript(url, function() {
				$.get("/Special:BuildWikihowModal?modal=expertise2&cat="+cat, function(data) {
					$.modal(data, {
						zIndex: 100000007,
						maxWidth: 410,
						minWidth: 410,
						overlayCss: { "background-color": "#000" }
					});
					WH.CategoryExpertise2.addHandlers();
				});
			});
		},

		addHandlers: function() {
			$(".expertise_2 #wh_modal_btn_learn").bind("mousedown", function() {
				$.modal.close();
				window.location.href = '/Special:EditFinder/Topic';
			});
			$(".expertise_2 #wh_modal_btn_not").bind("mousedown", function() {
				$.modal.close();
				mw.guidedTour.launchTourFromEnvironment();
			});
			$(".expertise_2 #wh_modal_close").bind("mousedown", function() {
				$.modal.close();
				mw.guidedTour.launchTourFromEnvironment();
			});
		}

	}

}(jQuery));
