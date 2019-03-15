(function($, mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.Quiz = {
		totalQuizzes: 0,
		currentScore: 0,
		allAnswered: false,
		lastRight: "This is the last question, but you missed something else. <a href=''>Double check this answer</a> for a perfect score.",
		allRight: "Congratulations, you aced the quiz! If you're ready to learn more, read ",

		init: function() {
			//set up the scores
			var $quizzes = $(".qz_container");
			WH.Quiz.totalQuizzes = $quizzes.length;
			$quizzes.find(".qz_denominator").html(WH.Quiz.totalQuizzes);

			if($("#relatedwikihows").length > 0) {
				var $related = $("#relatedwikihows").children().first();
				var target = $("a", $related).attr("href");
				var title = $(".related-title-text", $related).text();
				//make sure there's a space after How to
				if(title.indexOf("How to ") == -1) {
					title = title.replace("How to", "How to ");
				}
				WH.Quiz.allRight += " <a href='" + target + "'>" + title + "</a>";
			}

			$(document).on("change", ".qz_radio", function(e){
				WH.Quiz.answerQuiz(this);
			});

			//$(".qz_container").show();
		},

		answerQuiz: function(answer) {
			var isCorrect = $(answer).parent().hasClass("correct");

			var $container = $(answer).closest(".qz_container");
			var $answerObj = $(answer).closest(".qz_section");
			$container.addClass("answered");
			if($(".qz_container.answered").length == WH.Quiz.totalQuizzes) {
				WH.Quiz.allAnswered = true;
			}
			if(isCorrect) {
				$container.addClass("correct");
			} else {
				$container.removeClass("correct");
			}

			WH.Quiz.currentScore = $(".qz_container.correct").length;
			WH.Quiz.updateScore();

			if(WH.Quiz.allAnswered) {
				if (WH.Quiz.currentScore == WH.Quiz.totalQuizzes) {
					$(".qz_explanation span", $answerObj).html(WH.Quiz.allRight);
					$answerObj.trigger("lastAnswerCorrect");
				} else if (isCorrect) {
					var id = $(".qz_container.answered:not(.correct):first").attr("id").substring(13); //qz_container_3
					$(".qz_explanation span", $answerObj).html(WH.Quiz.lastRight).find("a").attr("href", "#qz_anchor_"+id);
				}
			}

			var $quiz_yourself_cta = $container.next('.qy_cta');
			if ($quiz_yourself_cta.length) $quiz_yourself_cta.slideDown();

			WH.maEvent('quiz_answer_click', { correct: isCorrect?1:0, articleTitle: mw.config.get('wgPageName') }, true);
		},

		updateScore: function() {
			$(".qz_numerator").html(WH.Quiz.currentScore);
		}

	};

	WH.Quiz.init();
}($, mw));
