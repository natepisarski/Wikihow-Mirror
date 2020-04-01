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
					WH.maEvent("cat_dialog_step2_show", { category: 'cat_dialog' }, false);
				});
			});
		},
		
		addHandlers: function() {
			$(".expertise_2 #wh_modal_btn_learn").bind("mousedown", function() {
				$.modal.close();
				WH.maEvent("cat_dialog_step2_yes", { category: 'cat_dialog' }, false);
				window.location.href = '/Special:EditFinder/Topic';
			});
			$(".expertise_2 #wh_modal_btn_not").bind("mousedown", function() {
				$.modal.close();
				WH.maEvent("cat_dialog_step2_no", { category: 'cat_dialog' }, false);
				mw.guidedTour.launchTourFromEnvironment();
			});
			$(".expertise_2 #wh_modal_close").bind("mousedown", function() {
				$.modal.close();
				WH.maEvent("cat_dialog_step2_x", { category: 'cat_dialog' }, false);
				mw.guidedTour.launchTourFromEnvironment();
			});
		}
		
	}
	
}(jQuery));