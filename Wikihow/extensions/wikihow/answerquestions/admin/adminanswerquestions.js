(function($, mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.AdminAnswerQuestions = {
		toolURL: '/Special:AdminAnswerQuestions',

		init: function () {
			$("#aq_new_cats_submit").on("click", function(e){
				e.preventDefault();
				WH.AdminAnswerQuestions.processNewCategories();
			});
			$("#aq_delete_cats").on("click", function(e){
				e.preventDefault();
				WH.AdminAnswerQuestions.deleteCategories();
			});
		},

		deleteCategories: function(){
			var catsToDelete = [];
			$('input[type=checkbox]:checked').each(function(){
				catsToDelete.push($(this).val());
			});

			$.post(
				WH.AdminAnswerQuestions.toolURL,
				{
					action: "delete",
					cats: JSON.stringify(catsToDelete)
				},
				function(result) {
					$('input[type=checkbox]:checked').each(function(){
						$(this).parent().remove();
					});
				},
				"json"
			);
		},

		processNewCategories: function(){
			var catString = $("#aq_new_cats").val();
			if(catString == "") {
				return;
			}
			$("#aq_new_success").html("");
			$("#aq_new_error").html("");
			$.post(
				WH.AdminAnswerQuestions.toolURL, 
				{
					action: "add",
					cats: catString,
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


	WH.AdminAnswerQuestions.init();
}($, mw));
