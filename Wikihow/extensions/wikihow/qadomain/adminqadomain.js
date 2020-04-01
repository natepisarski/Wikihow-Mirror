(function($, mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.AdminQADomain = {
		toolURL: '/Special:AdminQADomain',

		init: function () {
			$(document).on("click", "#qa_new_ids_submit", function(e){
				e.preventDefault();
				WH.AdminQADomain.processNewIds();
			});
			$(document).on("click", "#qa_delete_ids_submit", function(e){
				e.preventDefault();
				WH.AdminQADomain.deleteIds();
			});
		},

		deleteIds: function(){
			var idString = $("#qa_ids").val();
			if(idString == "") {
				return;
			}

			$.post(
				WH.AdminQADomain.toolURL,
				{
					action: "delete",
					ids: idString
				},
				function(result) {

				},
				"json"
			);
		},

		processNewIds: function(){
			var idString = $("#qa_ids").val();
			if(idString == "") {
				return;
			}
			$("#aq_new_success").html("");
			$("#aq_new_error").html("");
			$.post(
				WH.AdminQADomain.toolURL,
				{
					action: "add",
					ids: idString,
				},
				function(result){
					if(result.valid > 0) {
						$("#aq_new_success").html(result.valid + " categories added.");
						//add categories to top list
						var $catList = $("#aq_cat_list");
						for(i = 0; i < result.validCats.length; i++) {
							$catList.append("<li>" + result.validCats[i] + " <input type='checkbox' name='categories' value='" + result.validCats[i] + "'</li>")
						}
					}
					if(result.invalid > 0) {
						var errorMessage = result.invalid + " categories could not be added:<br />";
						for(i = 0; i < result.invalidCats.length; i++) {
							errorMessage += result.invalidCats[i] + "<br />";
						}
						$("#aq_new_error").html(errorMessage);
					}
				},
				"json"
			);
		}

	}


	WH.AdminQADomain.init();
}($, mw));
