(function($, mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.AdminWikiVisualLibrary = {
		toolURL: '/Special:AdminWikiVisualLibrary',

		init: function () {
			$(document).on("click", "#add_btn", function(e){
				e.preventDefault();
				WH.AdminWikiVisualLibrary.addArtist();
			});
			
			$(document).on("click", "#remove_btn", function(e){
				e.preventDefault();
				WH.AdminWikiVisualLibrary.removeArtist();
			});
		},

		addArtist: function() {
			var creatorName = $("#creator_name_add").val();
			var creatorType = $("#creator_type").val();

			if(creatorName != "" && creatorType != null) {
				$.post(
					WH.AdminWikiVisualLibrary.toolURL,
					{
						action: "add",
						creator: creatorName,
						type: creatorType
					},
					function(data) {
						alert("Artist has been added.");
					}
				);
			}
		},

		removeArtist: function(){
			var creatorId = $("#creator_remove").val();
			if(creatorId != null) {
				$.post(
					WH.AdminWikiVisualLibrary.toolURL,
					{
						action: "delete",
						id: creatorId
					},
					function(data) {
						alert("Artist has been disabled.");
					}
				);
			}
		}

	}


	WH.AdminWikiVisualLibrary.init();
}($, mw));
