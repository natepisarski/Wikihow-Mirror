(function() {
	"use strict";
	window.WH = window.WH || {};
	window.WH.QAExpertTest = {

		init: function() {
			$(".qa_expert_area").each(function(index, elem){
				var $qaanswers = $(elem).parents(".qa_answers");

				var answer = $(".qa_answer.answer", $qaanswers).text();
				//truncate it and add a button
				answer = answer.substring(0, 100) + "<span style='display:none;' class='qaexperttest_answerend'>" + answer.substring(100) + "</span><span class='qaexperttest_elipsis'>...</span><br />";

				var button = "<a href='#' class='button primary qaexperttest_cta'>View Expert Answer</a>";

				var popup = "<div class='qaexperttest_popup' style='display:none;'>We are going to give this to you for free! But we are trying to learn what someone might pay for it.<br/>What is the most you'd be willing to pay to see this expert answer?<br />";
				popup += "<input type='radio' value='.05' /><label>$0.05</label><input type='radio' value='.25'><label>$0.25</label><input type='radio' value='1'><label>$1</label><input type='radio' value='na' /><label>I prefer not to answer</label>";
				$(".qa_answer.answer", $qaanswers).html(answer + button + popup);
			});

			$(".qaexperttest_cta").one("click", function(e) {
				e.preventDefault();
				WH.event('article_button_expertQandA_test_click_open_ecd', { } );
				$(this).parent().find(".qaexperttest_popup").show();
				$(this).parent().find(".qaexperttest_popup input").on("click", function(e){
					e.preventDefault();
					WH.event('article_popup_expertQandA_test_click_choose_ecd', {'price':$(this).val() } );
					$(this).parent().hide();
					$(".qaexperttest_cta").hide();
					$(".qaexperttest_answerend").show();
					$(".qaexperttest_elipsis").hide();
				});
			});
		}
	};

	$(document).ready(function() {
		WH.QAExpertTest.init();
	});
})();
