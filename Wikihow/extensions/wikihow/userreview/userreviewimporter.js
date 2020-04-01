(function($, mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.UserReviewImporter = {
		toolUrl: "/Special:UserReviewImporter",

		init: function () {
			this.addEventHandlers();
		},

		addEventHandlers: function() {
			$(document).on('click', '#ur_curated_btn', this.importCurated);
			$(document).on('click', '#ur_uncurated_btn', this.importUncurated);
		},

		importCurated: function(e){
			e.preventDefault();
			if ($(this).hasClass("disabled")) {
				return;
			}

			$(this).addClass("disabled");
			$("#waiting_curated").show();
			$("#ur_curated_results").html("");
			var button = this;
			$.post(
				this.toolUrl,
				{
					a: 'importCurated'
				},
				function(result) {
					$("#ur_curated_results").html(result);
					$(button).removeClass("disabled");
					$("#waiting_curated").hide();
				}

			);
		},

		importUncurated: function(e){
			e.preventDefault();
			if ($(this).hasClass("disabled")) {
				return;
			}

			$(this).addClass("disabled");
			$("#waiting_uncurated").show();
			$("#ur_uncurated_results").html("");
			var button = this;
			$.post(
				this.toolUrl,
				{
					a: 'importUncurated'
				},
				function(result) {
					$("#ur_uncurated_results").html(result);
					$(button).removeClass("disabled");
					$("#waiting_uncurated").hide();
				}

			);
		}

	};

	$(document).ready(function(){
		WH.UserReviewImporter.init();
	});
}($, mw));
