(function($, mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.AnswerQuestions = {
		toolURL: '/Special:AnswerQuestions',
		QAEndpoint: '/Special:QA',
		maxAnswerChars: 700,
		EVENT_VERSION: 'answer_questions_tool',
		EVENT_CAT: 'qa',
		messageDelay: 1000,
		cosineThreshold: .6,
		defaultQuestion: "Choose a Category",
		defaultHeader: "Category",
		currentCategory: "",
		numQuestionsToShow: 5,
		answerMessages: ["Thanks for your answer!", "Nice! Can you answer another?", "Awesome, thanks for helping.", "Great answer, keep 'em coming!", "You just made someone's day!", "Great job. What else can you answer?", "Thanks for sharing your knowledge!", "Awesome, that's going to help a lot of people."],
		validator: null,
		isAdmin: false,
		isStaff: false,

		init: function () {
			this.isAdmin = $("#qat_container").hasClass("qat_admin");
			this.isStaff = $("#qat_container").hasClass("qat_staff");

			$(".qat_showmore").addClass("hidden");
			$("#bodycontents").on("click", ".qat_answer_button", function(e){
				e.preventDefault();
				var $topElement = $(this).closest(".qat_question_box");
				if(!$topElement.hasClass("dup_processed")) {
					WH.AnswerQuestions.checkForDuplicates($(this).closest(".qat_question_box"));
				}
				$topElement.find(".qat_duplicate_alert").show();
				$(this).parent().hide();
				$(this).parent().parent().find(".qat_answer_box").show();
				$(".qat_question_box:not('.hidden')").not($topElement).slideUp(function(){
					$(this).addClass("temporary_hide").attr("style", "");
				});
				$(".qat_showmore").addClass("temporary_hide");
			});

			$(document).on("click", ".qat_cancel", function(e){
				e.preventDefault();
				$(this).parent().hide();
				$(this).parent().parent().find(".qat_question_show").show();
				$(".temporary_hide").removeClass("temporary_hide");
				WH.AnswerQuestions.clearDuplicateInfo();
			});

			$(document).on('keyup', '.qat_answer', function() {
				var ans_chars = $(this).val().length;
				var max = $(this).parent().parent().find('.qat_error');

				if (!max.is(':visible') && ans_chars > WH.AnswerQuestions.maxAnswerChars) {
					max.slideDown();
					$(this).parent().find('.qat_submit').fadeOut();
				}
				else if (max.is(':visible') && ans_chars <= WH.AnswerQuestions.maxAnswerChars) {
					max.slideUp();
					$(this).parent().find('.qat_submit').fadeIn();
				}
			});

			$(document).on('click', ".qat_nexttopic", function(e){
				e.preventDefault();
				WH.AnswerQuestions.nextTopic();
			});

			$(document).on('click', '.qat_submit', function(e) {
				e.preventDefault();
				WH.AnswerQuestions.submit(this);
				WH.AnswerQuestions.clearDuplicateInfo();
			});

			$(document).on('click', '.qat_showmore', function(e){
				e.preventDefault();
				WH.AnswerQuestions.showMore();
			});

			$(document).on('click', '.qat_duplicate_button',  function(e) {
				e.preventDefault();
				var label = "duplicate before score";
				if($(this).closest("qat_section").hasClass("qat_duplicate_alert")) {
					label = "duplicate after score";
				}
				WH.AnswerQuestions.removeQuestion(this, true, true, $(this).closest(".qat_question_box").find(".qat_question_show .qat_question").html(), label);
				WH.AnswerQuestions.clearDuplicateInfo();
			});

			$(document).on('click', '.qat_flag_button',  function(e) {
				e.preventDefault();
				WH.AnswerQuestions.removeQuestion(this, false, $(this).closest(".qat_question_box").find(".qat_question_show .qat_question").html());
				WH.AnswerQuestions.clearDuplicateInfo();
			});

			$(document).on('click', '.qat_notsure', function(e){
				e.preventDefault();
				$(this).closest(".qat_question_box").slideUp(function(){
					$(this).remove();
					WH.AnswerQuestions.checkEndOfQuestions();
				});
			});

			$(document).on('mouseenter', '.has_dup .qat_question_show .qat_duplicate_button', function(e){
				e.preventDefault();
				$(this).parent().find(".qat_dup_tooltip").show();
			});
			$(document).on('mouseleave', '.has_dup .qat_question_show .qat_duplicate_button', function(e){
				e.preventDefault();
				$(this).parent().find(".qat_dup_tooltip").hide();
			});
			$("#qat_cat_top li").hover(
				function(){
				$(this).addClass("hover");
			}, function(){
				$(this).removeClass("hover");
			});
			$(document).on('mouseenter', '.qat_mark', function(e){
				$(this).find(".qat_menu").show();
			});

			$(document).on('mouseleave', '.qat_mark', function(e){
				$(this).find(".qat_menu").hide();
			});

			//select a top level category
			$(document).on('click', '#qat_cat_top a', function(e){
				e.preventDefault();
				$(this).addClass('selected');
				var id = $(this).attr("id").substring(4); //get the category name from the id
				$("#qat_cat_"+id).show();
				$("#qat_cat_top").hide();
			});
			$(document).on("click", ".qat_cat_sub", function(e){
				e.preventDefault();
				$(".qat_cat_sub").removeClass("selected");
				$(this).addClass("selected");
				WH.AnswerQuestions.selectCategory();
				$("#qat_cat_container").slideUp(function(){
					$(".qat_cat_chooser h3").html(WH.AnswerQuestions.defaultHeader);
					$("#qat_cat_info span").html(WH.AnswerQuestions.currentCategory).show();
					$("#qat_open_cat").show();
				});
				$("#qat_start").hide();
				$("#qat_container").show();
			});
			$(document).on("click", ".qat_cat_totop", function(e){
				e.preventDefault();
				WH.AnswerQuestions.resetCategorySelector();
			});
			$(document).on("click", "#qat_open_cat", function(e){
				e.preventDefault();
				WH.AnswerQuestions.reopenCategorySelector();
			});
			$(document).on("click", "#qat_change_cancel", function(e){
				e.preventDefault();
				WH.AnswerQuestions.closeCategorySelector();
			});

			$(document).on("click", ".start_link", function(e){
				e.preventDefault();
				var cat = $(this).attr("id").substring(6);
				var categories = $("#qat_cat_"+cat+" .qat_cat_sub");
				var randomCat = Math.floor(Math.random()*categories.length);
				$(categories[randomCat]).trigger("click");
			});

			$(document).on("click", "#qat_menu a", function(e){
				e.preventDefault();
				$("#qat_menu a").removeClass("active");
				$(this).addClass("active");
				if($(this).attr("id") == "qat_new") {
					$("#qat_category_type").val("new");
					//hide old ones
					$(".qat_question_old_1").hide();
				} else {
					$("#qat_category_type").val("");
					//show any old ones
					$(".qat_question_old_1").show();
				}
			});

			if($("#qat_group").val() != "") {
				$("#qat_start").hide();
				$("#qat_container").show();
				WH.AnswerQuestions.getQuestions();
			} else {
				if($("#qat_user_category").val() != "") {
					WH.AnswerQuestions.currentCategory = $("#qat_user_category").val();
					$(".qat_cat_chooser h3").html(WH.AnswerQuestions.defaultHeader);
					$("#qat_cat_info span").html(WH.AnswerQuestions.currentCategory);
					$("#qat_cat_info").show();
					$("#qat_start").hide();
					$("#qat_container").show();
					WH.AnswerQuestions.getQuestions();
				} else {
					WH.AnswerQuestions.resetCategorySelector();
					$("#qat_start").fadeIn();
					$("#qat_change_cancel").hide();
					$("#qat_cat_container").slideDown();
				}
			}

		},

		removeQuestion: function(button, isDuplicate, question, label) {
			var action;
			var $parent = $(button).closest(".qat_section");
			var sqid = $parent.parent().attr('id');
			var buttonText = $(button).text();
			if(WH.AnswerQuestions.isAdmin || isDuplicate) {
				WH.whEvent(WH.AnswerQuestions.EVENT_CAT, 'click_ignore_submitted', '', '', WH.AnswerQuestions.EVENT_VERSION);
				WH.maEvent("answertool_question_removed", {
					category: 'qa',
					qs_id: sqid,
					question: question,
					page_id: $("#qat_article_id").val(),
					group_name: $("#qat_group").val(),
					expert_id: $("#qat_expert").val(),
					welcome_msg_name: $("#qat_welcome_name").val(),
					link_text: buttonText,
					button_identity: label
				}, true);
				action = 'sq_ignore';
			} else {
				WH.whEvent(WH.AnswerQuestions.EVENT_CAT, 'click_flag_submitted', '', '', WH.AnswerQuestions.EVENT_VERSION);
				WH.maEvent("answertool_question_flagged", {
					category: 'qa',
					qs_id: sqid,
					question: question,
					page_id: $("#qat_article_id").val(),
					group_name: $("#qat_group").val(),
					expert_id: $("#qat_expert").val(),
					welcome_msg_name: $("#qat_welcome_name").val(),
					link_text: buttonText
				}, true);
				action = 'sq_flag'
			}
			$.post(
				WH.AnswerQuestions.QAEndpoint,
				{
					a: action,
					sqid: sqid
				},
				function() {
					$parent.parent().find(".qat_section ").slideUp();
					$parent.parent().find(".qat_feedback_remove").show().delay(WH.AnswerQuestions.messageDelay).fadeOut(function(){
						$parent.parent().slideUp(function(){
							$(this).remove();
							WH.AnswerQuestions.checkEndOfQuestions();
						});
					});
				}
			);
		},

		getQuestions: function(){
			$.getJSON(
				this.toolURL,
				{
					action: 'getNext',
					group: $("#qat_group").val(),
					expert: $('#qat_expert').val(),
					category: $('#qat_user_category').val(),
					queue: $("#qat_category_type").val()
				},
				function(data) {
					WH.AnswerQuestions.processQuestions(data);
				}
			);
		},

		nextTopic: function() {
			$.getJSON(
				this.toolURL,
				{
					action: 'skip',
					aid: $("#qat_article_id").val(),
					group: $("#qat_group").val(),
					expert: $('#qat_expert').val(),
					category: $('#qat_user_category').val(),
					queue: $("#qat_category_type").val()
				},
				function(data) {
					WH.AnswerQuestions.processQuestions(data);
				}
			);
		},

		processQuestions: function(data) {
			if(data.error) {
				$("#qat_queue_error").show();
				$("#qat_container").hide();
				$(".qat_sidebar ul").hide();
				$(".qat_sidebar #qat_no_dup").show();
				$(".qat_sidebar #qat_has_dup").hide();
				$("#qat_exp_answered").hide();
				WH.AnswerQuestions.resetCategorySelector();
				$("#qat_cat_container").slideDown();
				$(".qat_sidebar").hide();
			} else {
				$(".qat_sidebar").show();
				$("#qat_question_container").html(data.qhtml);
				$("#qat_title").attr("href", data.link).html(data.title);
				//count how many we have
				if($("#qat_question_container li").length > WH.AnswerQuestions.numQuestionsToShow) {
					$("#qat_question_container li:gt("+(WH.AnswerQuestions.numQuestionsToShow-1)+")").addClass("hidden");
					$(".qat_showmore").removeClass("hidden temporary_hide");
				} else {
					$(".qat_showmore").addClass("hidden");
				}

				$("#qat_article_id").val(data.aid);
				if(data.exp_answered != "") {
					$("#qat_exp_answered").html(data.exp_answered).show();
				}
				else {
					$("#qat_exp_answered").hide();
				}
				var $list = $(".qat_sidebar ul");
				if(data.aqs.length > 0) {
					$list.show();
					$list.html("");
					$(".qat_sidebar #qat_has_dup").show();
					$(".qat_sidebar #qat_no_dup").hide();
					for(i = 0; i < data.aqs.length; i++ ) {
						$list.append("<li id='aq_" + i + "'>" + data.aqs[i] + "</li>");
					}
					//start processing the duplicates
					$(".qat_question_box").each(function(){
						WH.AnswerQuestions.checkForDuplicates($(this));
					})
				} else {
					$list.hide();
					$(".qat_sidebar #qat_no_dup").show();
					$(".qat_sidebar #qat_has_dup").hide();
				}
			}
		},

		submit: function(obj) {
			var id = $(obj).parent().parent().attr('id');
			var $parent = $('#'+id);
			var isExpert = $("#qat_expert").val() != "";
			var isLowContrib = $("#qat_few_contribs").val() == "1";

			$parent.find(".qat_error_min").hide();

			var data = {
				a: 'pa',
				aid: $("#qat_article_id").val(),
				sqid: id,
				question: $parent.find('.qat_question_txt').val(),
				answer: $parent.find('.qat_answer').val(),
				verifier_id: $("#qat_expert").val(),
				submitter_user_id: 1 //triggers server-side userid grab
			};

			// Answer to a proposed answer submission must be valid.  If it isn't, discard the submission and
			// display a success message anyway as if the answer successfully submitted.
			var answerValid, answerRules, questionValid;
			answerValid = WH.AnswerQuestions.isValidProposedAnswer(data.sqid, data.question, data.answer);
			if (answerValid) {
				questionValid  = WH.AnswerQuestions.isValidProposedAnswerQuestion(data.sqid, data.question, data.answer);
			}
			else {
				answerRules = this.validator.getFailedRules();
				if (answerRules.indexOf(this.validator.short_rule) != -1) {
					$parent.find(".qat_error_min").show();
					return;
				}
				else if (answerRules.indexOf(this.validator.url_rule) != -1) {
					$parent.find(".qat_error_url").show();
					return;
				}
				else if (answerRules.indexOf(this.validator.phone_rule) != -1) {
					$parent.find(".qat_error_phone").show();
					return;
				}
			}
			if (!isExpert && isLowContrib && (!answerValid || !questionValid)) {
				WH.AnswerQuestions.finishAnswering($parent);
				return;
			}

			if (data.question.length && data.answer.length) {
				$.post(
					WH.AnswerQuestions.QAEndpoint,
					data,
					function(result) {
						if (!result.userBlocked) {
							var label = 'Q: '+data.question+' | A: '+data.answer;
							WH.whEvent(WH.AnswerQuestions.EVENT_CAT, 'proposed_answer_submission', '', label, WH.AnswerQuestions.EVENT_VERSION);
							WH.maEvent("answertool_answer_submitted", {
								category: 'qa',
								qs_id: data.sqid,
								answer: data.answer,
								question: data.question,
								page_id: data.aid,
								group_name: $("#qat_group").val(),
								expert_id: data.verifier_id,
								welcome_msg_name: $("#qat_welcome_name").val()
							}, true);
						}
						WH.AnswerQuestions.finishAnswering($parent);
					},
					'json'
				);
			}

		},

		finishAnswering: function($parent) {
			$parent.find(".qat_answer_box").hide();
			var message = WH.AnswerQuestions.answerMessages[Math.floor(Math.random()*WH.AnswerQuestions.answerMessages.length)];
			$parent.find(".qat_feedback_answer").html(message).show().delay(WH.AnswerQuestions.messageDelay).fadeOut(function(){
				$parent.slideUp(function(){
					$parent.remove();
					WH.AnswerQuestions.checkEndOfQuestions();
				});
				$(".temporary_hide").removeClass("temporary_hide");
			});
		},

		isValidProposedAnswerQuestion: function(sqid, question, answer) {
			var isValid = true;

			var config = {
				email: true,
				phone: true,
				url: true
			};

			var validator = new WH.StringValidator(config);
			isValid = validator.validate(question);

			if (!isValid) {
				var rules = validator.getFailedRules();
				WH.whEvent(WH.AnswerQuestions.EVENT_CAT, 'proposed_answer_question_discarded', rules.join(","), question, WH.AnswerQuestions.EVENT_VERSION);
				WH.maEvent("answertool_question_discarded", {
					category: 'qa',
					qs_id: sqid,
					answer: answer,
					question: question,
					page_id: $("#qat_article_id").val(),
					group_name: $("#qat_group").val(),
					expert_id: $("#qat_expert").val(),
					welcome_msg_name: $("#qat_welcome_name").val(),
					reason: rules.join(",")
				}, true);
			}

			return isValid;
		},

		isValidProposedAnswer: function(sqid, question, answer) {
			var isValid = true;

			if (WH.AnswerQuestions.isStaff) {
				var config = {
					minlength: 75,
					email: true
				};
			}
			else {
				var config = {
					minlength: 75,
					email: true,
					phone: true,
					url: true
				};
			}

			var validator = this.validator = new WH.StringValidator(config);
			isValid = validator.validate(answer);

			if (!isValid) {
				var rules = validator.getFailedRules();
				WH.whEvent(WH.AnswerQuestions.EVENT_CAT, 'proposed_answer_discarded', rules.join(","), answer, WH.AnswerQuestions.EVENT_VERSION);
				WH.maEvent("answertool_answer_discarded", {
					category: 'qa',
					qs_id: sqid,
					answer: answer,
					question: question,
					page_id: $("#qat_article_id").val(),
					group_name: $("#qat_group").val(),
					expert_id: $("#qat_expert").val(),
					welcome_msg_name: $("#qat_welcome_name").val(),
					reason: rules.join(",")
				}, true);
			}

			return isValid;
		},

		showMore: function(){
			$(".qat_showmore").addClass("hidden");
			$("#qat_question_container li.hidden").each(function(){
				$(".qat_question_show", this).hide();
				$(this).slideDown(function(){
					$("#qat_question_container li.has_dup").each(function(){
						WH.AnswerQuestions.positionTooltip($(this));
					});
				}).removeClass("hidden");
				$(".qat_question_show", this).fadeIn();
			});
		},

		checkEndOfQuestions: function(){
			//first check to see if there are any hidden questions left to show
			if($("#qat_question_container li.hidden").length > 0) {
				$("#qat_question_container li.hidden:first").slideDown().removeClass("hidden").attr("style", "");
				if($("#qat_question_container li.hidden").length == 0) {
					$(".qat_showmore").addClass("hidden");
				}
			} else {
				//no more, so are there any more questions at all?
				if($("#qat_question_container li:visible").length == 0) {
					WH.AnswerQuestions.getQuestions();
				}
			}
		},

		checkForDuplicates: function($topElement) {
			var currentQuestion = $topElement.find(".qat_question").html();
			var score;
			var maxScore = {"score": 0, "id": ""};
			$(".qat_sidebar li").each(function(index, element){
				score = WH.AnswerQuestions.cosineSimilarity(currentQuestion, $(element).html());
				if(score > maxScore.score) {
					maxScore.score = score;
					maxScore.id = $(element).attr("id");
				}
			});
			console.log("max score is " + maxScore.score); //Alissa is using this still
			if(maxScore.score > WH.AnswerQuestions.cosineThreshold) {
				var dupQuestion = $("#" + maxScore.id).html();
				$topElement.find(".qat_duplicate_alert span").html(dupQuestion);
				$topElement.find(".qat_dup_tooltip span").html(dupQuestion)
				//$("#" + maxScore.id).addClass("qat_dup"); //not highlighting right rail for now
				$topElement.addClass("has_dup");
				WH.AnswerQuestions.positionTooltip($topElement);
			} else {
				$topElement.find(".qat_duplicate_alert").remove();
			}
			$topElement.addClass("dup_processed");
		},

		cosineSimilarity: function(sentenceA, sentenceB) {
			// http://stackoverflow.com/questions/945724/cosine-similarity-vs-hamming-distance/1290286#1290286
			var tokensA = sentenceA.split(' ');
			var tokensB = sentenceB.split(' ');

			if (tokensA.length == 0 || tokensB.length == 0) return 0;

			var a = 0, b = 0, c = 0;
			var uniqueTokensA = [];
			var uniqueTokensB = [];

			var mergedArray = tokensA.concat(tokensB);
			var uniqueMergedTokens = mergedArray.filter(function(itm,i,mergedArray){
				return i==mergedArray.indexOf(itm);
			});

			for(i = 0; i < tokensA.length; i++) {
				uniqueTokensA[tokensA[i]] = 0;
			}
			for(i = 0; i < tokensB.length; i++) {
				uniqueTokensB[tokensB[i]] = 0;
			}

			for(i = 0; i < uniqueMergedTokens.length; i++) {
				var x = (typeof uniqueTokensA[uniqueMergedTokens[i]] != 'undefined') ? 1 : 0;  //isset($uniqueTokensA[$token]) ? 1 : 0;
				var y = (typeof uniqueTokensB[uniqueMergedTokens[i]] != 'undefined') ? 1 : 0;  //$y = isset($uniqueTokensB[$token]) ? 1 : 0;
				a += x * y;
				b += x;
				c += y;
			}
			return b * c != 0 ? a / Math.sqrt(b * c) : 0;
		},

		clearDuplicateInfo: function(){
			$(".qat_sidebar li").removeClass("qat_dup");
			$(".qat_duplicate_alert").hide();
		},

		positionTooltip: function($topElement){
			var $tooltip = $topElement.find(".qat_dup_tooltip");
			$tooltip.css("top", (-1*$tooltip.height() - 40) + "px");
		},

		selectCategory: function(){
			$("#qat_queue_error").hide();
			$("#qat_container").show();
			WH.AnswerQuestions.currentCategory = $('.qat_cat_sub.selected').html();
			$("#qat_user_category").val(WH.AnswerQuestions.currentCategory);
			$(".qat_cat_chooser h3").html(WH.AnswerQuestions.defaultHeader);
			$("#qat_cat_info span").html(WH.AnswerQuestions.currentCategory);
			$("#qat_cat_info").show();
			$.getJSON(
				this.toolURL,
				{
					action: 'setCategory',
					expert: $('#qat_expert').val(),
					category: WH.AnswerQuestions.currentCategory,
					queue: $("#qat_category_type").val()
				},
				function(data) {
					WH.AnswerQuestions.processQuestions(data);
				}
			);
		},

		resetCategorySelector: function(){
			$("#qat_open_cat").hide();
			$("#qat_change_cancel").show();
			$("#qat_cat_top").show();
			$("#qat_cat_top a").removeClass("selected");
			$(".qat_cat_subgroup").hide();
			$(".qat_cat_sub").removeClass("selected");
			$(".qat_cat_chooser h3").html(WH.AnswerQuestions.defaultQuestion);
			$("#qat_cat_info").hide();
		},

		reopenCategorySelector: function() {
			$("#qat_change_cancel").show();
			$("#qat_open_cat").hide();
			$(".qat_cat_chooser h3").html(WH.AnswerQuestions.defaultQuestion);
			$("#qat_cat_info").hide();
			var $selectedElem = $("#qat_cat_top a.selected");
			if($selectedElem.length > 0) {
				var id = $("#qat_cat_top a.selected").attr("id").substring(4);
				$("#qat_cat_" + id).show();
				$("#qat_cat_top").hide();
			} else {
				$("#qat_cat_top").show();
			}
			$("#qat_cat_container").slideDown();
		},

		closeCategorySelector: function(){
			$("#qat_change_cancel").hide();
			$("#qat_cat_container").slideUp(function(){
				$(".qat_cat_chooser h3").html(WH.AnswerQuestions.defaultHeader);
				$("#qat_cat_info span").html(WH.AnswerQuestions.currentCategory);
				$("#qat_cat_info").show();
				$("#qat_open_cat").show();
			});
		}

	}


	WH.AnswerQuestions.init();
}($, mw));
