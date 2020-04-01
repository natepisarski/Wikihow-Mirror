(function() {
	"use strict";
	window.WH = window.WH || {};
	window.WH.QABox = {

		toolURL: '/Special:QABox',
		event_version: 'answer_box',
		EVENT_CAT: "qa",
		isStaff: $('#qab_staff').length > 0,
		max_ans_chars: 700,
		min_ans_chars: 75,
		EVENT_PROPOSED_ANSWER_SUBMISSION: 'qabox/proposed_answer_submitted',
		validator: null,

		init: function() {
			this.loadInitialData();

			$(document).on('click', '#qab_refresh', function() {
				WH.whEvent(WH.QABox.EVENT_CAT, 'answer_box_refresh', '', '', WH.QABox.event_version);
				WH.QABox.refreshData();
			});

			$(document).on('click', '.qab_a_area', function() {
				if (!$(this).hasClass('qab_a_fullarea')) {
					$(this).addClass('qab_a_fullarea');
				}
			});

			$(document).on('click', '.qab_a_fullarea .qab_submit', function() {
				WH.QABox.submit($(this));
			});

			//max character alert
			$(document).on('keyup', '.qab_answer', function() {
				var ans_chars = $(this).val().length;
				var max = $(this).parent().parent().find('.qab_maxed');

				if (!max.is(':visible') && ans_chars > WH.QABox.max_ans_chars) {
					max.slideDown();
					$(this).parent().find('.qab_submit').fadeOut();
				}
				else if (max.is(':visible') && ans_chars <= WH.QABox.max_ans_chars) {
					max.slideUp();
					$(this).parent().find('.qab_submit').fadeIn();
				}
			});
		},

		loadInitialData: function() {
			$.get( this.toolURL,
				{ action: 'load',
				  articleid: mw.config.get('wgArticleId') },
				function(data) {
					if (data && data.html) $('#qa_box').replaceWith(data.html);
				},
				'json'
			);
		},

		refreshData: function(answered_sqid) {
			$.post( this.toolURL,
				{ action: 'refresh',
				  articleid: mw.config.get('wgArticleId'),
				  asqid: typeof answered_sqid != 'undefined' ? answered_sqid : '' },
				function(data) {
					if (data && data.html) $('#qa_box').replaceWith(data.html);
				},
				'json'
			);
		},

		submit: function(obj) {
			var id = $(obj).parent().parent().parent().attr('id');
			$(obj).closest('.qab_container').find(".qab_min").hide();

			var data = {
				a: 'pa',
				aid: $('#'+id).find('.qab_article_id').val(),
				sqid: id,
				question: $('#'+id).find('.qab_q_txt').html(),
				answer: $('#'+id).find('.qab_answer').val(),
				email:  $('#'+id).find('.qab_email').val(),
			};

			// Answer to a proposed answer submission must be valid.  If it isn't, discard the submission and
			// display a success message anyway as if the answer successfully submitted.
			var answerValid, answerRules, questionValid;
			answerValid = WH.QABox.isValidProposedAnswer(data.answer);
			if(!answerValid) {
				answerRules = this.validator.getFailedRules();
			}
			questionValid = WH.QABox.isValidProposedAnswerQuestion(data.question);
			if(!answerValid && answerRules.indexOf(this.validator.short_rule) != -1) {
				$(obj).closest('.qab_container').find(".qab_min").show();
				return;
			}
			if (!answerValid || !questionValid) {
				WH.QABox.refreshData();
				return;
			}

			if (data.question.length && data.answer.length) {

				$('#qab_inner').fadeOut(function() {
					$(this)
						.html(mw.message('qab_thanks').text())
						.fadeIn()
						.delay(800)
						.fadeOut(function() {
							var label = 'Q: '+data.question+' | A: '+data.answer;

							$.post(
								'/Special:QA',
								data,
								function(result) {
									if (!result.userBlocked) {
										WH.whEvent(WH.QABox.EVENT_CAT, 'proposed_answer_submission', '', label, WH.QABox.event_version);
										$.publish(WH.QABox.EVENT_PROPOSED_ANSWER_SUBMISSION);
									}
									WH.QABox.refreshData(id);
								},
								'json'
							);
						});
				});
			}

		},

		isValidProposedAnswerQuestion: function(question) {
			var isValid = true;

			var config = {
				email: true,
				phone: true,
				url: true
			};

			var validator = this.validator = new WH.StringValidator(config);
			isValid = validator.validate(question);

			if (!isValid) {
				var rules = validator.getFailedRules();
				WH.whEvent(WH.QABox.EVENT_CAT, 'proposed_answer_question_discarded', rules.join(","), question);
			}

			return isValid;
		},

		isValidProposedAnswer: function(answer) {
			var isValid = true;

			if (WH.QABox.isStaff) {
				var config = {
					minlength: WH.QABox.min_ans_chars,
					email: true
				};
			}
			else {
				var config = {
					minlength: WH.QABox.min_ans_chars,
					email: true,
					phone: true,
					url: true
				};
			}

			var validator = this.validator = new WH.StringValidator(config);
			isValid = validator.validate(answer);

			if (!isValid) {
				var rules = validator.getFailedRules();
				WH.whEvent(WH.QABox.EVENT_CAT, 'proposed_answer_discarded', rules.join(","), answer, WH.QABox.event_version);
			}

			return isValid;
		}
	}

	$(document).ready(function() {
		var rand = Math.floor(Math.random() * 100) + 1;

		//only show for 40% of users
		if (rand <= 40) {
			WH.QABox.init();
		}
	});

})(jQuery);
