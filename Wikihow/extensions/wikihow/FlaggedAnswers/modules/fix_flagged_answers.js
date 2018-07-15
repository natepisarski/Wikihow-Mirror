(function($) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.FixFlaggedAnswers = {

		tool_url: '/Special:FixFlaggedAnswers',
		qa_url: '/Special:QA',
		skipping: false,
		question_min: 20,
		question_max: 500,
		answer_min: 60,
		answer_max: 1000,
		qfa_data: {},
		is_staff: $('#ffa_staff').length > 0,

		init: function() {
			this.addHandlers();
			this.getNext();
		},

		getNext: function() {
			var url = this.tool_url + '?action=next';
			if (this.skipping) url += '&skip='+this.qfa_data['qfa_id'];

			if (!WH.QAWidget) mw.loader.load(['ext.wikihow.qa_widget'], null, true);

			$.getJSON(
				url,
				$.proxy(function(result) {
					if (result.qfa_id) {
						this.qfa_data = {
							qfa_id: 						result.qfa_id,
							original_question: 	result.curatedQuestion.text,
							original_answer: 		result.curatedQuestion.curatedAnswer.text,
							reason: 						result.qfa_reason,
							article_title: 			result.article_title,
							aid: 								result.articleId,
							aqid: 							result.qfa_aq_id,
							sqid: 							result.curatedQuestion.submittedId,
							cqid: 							result.curatedQuestion.id,
							caid: 							result.curatedQuestion.curatedAnswer.id,
						};
						this.display(result);
					}
					else {
						this.displayEndOfQueue();
					}
				},this)
			);

			this.skipping = false;
		},

		addHandlers: function() {
			$('#ffa_main').on('click', '#ffa_edit, .ffa_qa_block', $.proxy(function() {
				this.editMode();
			},this));

			$('#ffa_skip').on('click', $.proxy(function() {
				this.skipping = true;
				this.getNext();
			},this));

			$('#ffa_cancel').on('click', $.proxy(function() {
				this.cancelEditMode();
			},this));

			$('#ffa_save').on('click', $.proxy(function() {
				this.save();
			},this));

			$('#ffa_delete_answer').on('click', $.proxy(function() {
				this.delete('delete_answer');
			},this));

			$('#ffa_delete_pair').on('click', $.proxy(function() {
				this.delete('delete_pair');
			},this));

			$('#ffa_remove_flag').on('click', $.proxy(function() {
				this.removeFlag();
				return false;
			},this));

			this.addKeyHandlers();
		},

		display: function(data) {
			$('#ffa_remaining p').fadeOut().html(data.remaining).fadeIn();
			$('#ffa_article_title a').fadeOut().attr('href',data.article_link).html(data.article_title).fadeIn();

			var html = Mustache.render(unescape($('#fix_flagged_answers_qa_section').html()), data);
			html = $('<textarea/>').html(html).text();

			$('#ffa_guts').slideUp().html(html).slideDown();
			$('#ffa_article_html').fadeOut().html(data.article_html).fadeIn();
		},

		editMode: function() {
			//make question editable
			var edit_ffa_q = $('<textarea id="ffa_q_edit" class="wh_block ffa_edit_ta" />');
			$('#ffa_q_text').replaceWith(edit_ffa_q);
			$(edit_ffa_q).html(this.qfa_data['original_question']);

			//make answer editable
			var edit_ffa_a = $('<textarea id="ffa_a_edit" class="wh_block ffa_edit_ta" />');
			$('#ffa_a_text').replaceWith(edit_ffa_a);
			$(edit_ffa_a).html(this.qfa_data['original_answer']);

			//swap buttons
			$('#ffa_view_btns').fadeOut(function() {
				$('#ffa_edit_btns').fadeIn();
			});
		},

		cancelEditMode: function() {
			var orig_q = $('<div id="ffa_q_text" class="ffa_q_a_text" />');
			$(orig_q).html(this.qfa_data['original_question']);
			$('#ffa_q_edit').replaceWith(orig_q);

			var orig_a = $('<div id="ffa_a_text" class="ffa_q_a_text" />');
			$(orig_a).html(this.qfa_data['original_answer']);
			$('#ffa_a_edit').replaceWith(orig_a);

			$('#ffa_edit_btns').fadeOut(function() {
				$('#ffa_view_btns').fadeIn();
			});

			$('.ffa_err').hide().html('');
		},

		save: function() {
			this.qfa_data['edited_question'] = $('#ffa_q_edit').val();
			this.qfa_data['edited_answer'] = $('#ffa_a_edit').val();

			var data = {
				a: 				'aq',
				aid: 			this.qfa_data['aid'],
				sqid: 		this.qfa_data['sqid'],
				aqid: 		this.qfa_data['aqid'],
				cqid: 		this.qfa_data['cqid'],
				caid: 		this.qfa_data['caid'],
				question: this.qfa_data['edited_question'],
				answer: 	this.qfa_data['edited_answer']
			};

			var answerValid, questionValid, reason, err;
			answerValid = this.isValidProposedAnswer(data.answer);

			if (answerValid) {
				questionValid = this.isValidProposedAnswerQuestion(data.question);
				if (!questionValid) {
					reason = this.validator.getFailedRules();
					if (reason.indexOf(this.validator.url_rule) != -1) {
						err = mw.msg('ffa_url');
					}
					else if (reason.indexOf(this.validator.short_rule) != -1) {
						err = mw.msg('ffa_too_short');
					}
					else if (reason.indexOf(this.validator.phone_rule) != -1) {
						err = mw.msg('ffa_phone');
					}
					else {
						err = mw.msg('ffa_err',reason[0]);
					}
					$('#ffa_q_err').html(err).show();
					return;
				}
			} else {
				reason = this.validator.getFailedRules();
				if (reason.indexOf(this.validator.url_rule) != -1) {
					err = mw.msg('ffa_url');
				}
				else if (reason.indexOf(this.validator.short_rule) != -1) {
					err = mw.msg('ffa_too_short');
				}
				else {
					err = mw.msg('ffa_err',reason[0]);
				}
				$("#ffa_a_err").html(err).show();
				return;
			}

			$('#ffa_guts').slideUp();

			if (data.question.length && data.answer.length) {
				$.post(
					this.qa_url,
					data,
					$.proxy(function(result) {
						this.log('edit');
						this.cancelEditMode();
						this.getNext();
					},this),
					'json'
				);
			}
		},

		addKeyHandlers: function() {
			//QUESTION
			$(document).on('keyup', '#ffa_q_edit', function() {
				//character limit
				if ($(this).val().length > WH.FixFlaggedAnswers.question_max) {
					$('#ffa_q_err').html(mw.msg('ffa_q_cl')).show();
					$('#ffa_edit_btns').slideUp();
					$(this).addClass('ffa_text_err');
				}
				else if ($(this).val().length <= WH.FixFlaggedAnswers.question_max) {
					$('#ffa_q_err').hide().html('');
					$('#ffa_edit_btns').slideDown();
					$(this).removeClass('ffa_text_err');
				}
			});

			//ANSWER
			$(document).on('keyup', '#ffa_a_edit', function() {
				//character limit
				if ($(this).val().length > WH.FixFlaggedAnswers.answer_max) {
					$('#ffa_a_err').html(mw.msg('ffa_a_cl')).show();
					$('#ffa_edit_btns').slideUp();
					$(this).addClass('ffa_text_err');
				}
				else if ($(this).val().length <= WH.FixFlaggedAnswers.answer_max) {
					$('#ffa_a_err').hide();
					$('#ffa_edit_btns').slideDown();
					$(this).removeClass('ffa_text_err');
				}
			});
		},

		delete: function(event) {
			var data = {
				a: 		'aq_delete',
				from: 'FixFlaggedAnswers',
				aqid: this.qfa_data['aqid'],
				cqid: this.qfa_data['cqid'],
				caid: this.qfa_data['caid'],
				sqid: this.qfa_data['sqid'],
				aid: 	this.qfa_data['aid']
			};

			data['answer_only'] = event == 'delete_answer' ? 1 : 0;

			$.post(
				this.qa_url,
				data,
				$.proxy(function(result) {
					this.log(event);
					this.getNext();
				},this),
				'json'
			);
		},

		removeFlag: function() {
			$.post(
				this.tool_url,
				{
					action: 'remove_flag',
					qfa_id: this.qfa_data['qfa_id']
				},
				$.proxy(function(result) {
					this.log('remove_flag');
					this.getNext();
				},this)
			);
		},

		displayEndOfQueue: function() {
			$('#ffa_remaining').fadeOut();
			$('#ffa_article_title a').fadeOut();
			$('#ffa_article_html').fadeOut();
			$('#ffa_main').slideUp(function() {
				var html = Mustache.render(unescape($('#fix_flagged_answers_eoq').html()));
				$('#ffa_main').html(html).slideDown();
			});
		},

		isValidProposedAnswerQuestion: function(question) {
			var isValid = true;

			var config = {
				minlength: this.question_min,
				email: true,
				phone: true,
				url: true
			};

			var validator = this.validator = new WH.StringValidator(config);
			isValid = validator.validate(question);

			if (!isValid) this.log('edited_answer_question_discarded');

			return isValid;
		},

		isValidProposedAnswer: function(answer) {
			var isValid = true;

			if (this.is_staff) {
				var config = {
					minlength: this.answer_min,
					email: true
				};
			}
			else {
				var config = {
					minlength: this.answer_min,
					email: true,
					phone: true,
					url: true
				};
			}

			var validator = this.validator = new WH.StringValidator(config);
			isValid = validator.validate(answer);

			if (!isValid) this.log('edited_answer_discarded');

			return isValid;
		},

		log: function(event_action) {
			var event = 'fix_flagged_answers';
			var eventProps = {
				category: 'fix_flagged_answers',
				action: event_action,
				article_title: this.qfa_data['article_title'],
				original_question: this.qfa_data['original_question'],
				edited_question: this.qfa_data['edited_question'],
				original_answer: this.qfa_data['original_answer'],
				edited_answer: this.qfa_data['edited_answer']
			};
			WH.maEvent(event, eventProps, false);

			var question = this.qfa_data['edited_question'] ? this.qfa_data['edited_question'] : this.qfa_data['original_question'];
			var answer = this.qfa_data['edited_answer'] ? this.qfa_data['edited_answer'] : this.qfa_data['original_answer'];

			$.post(
				this.tool_url,
				{
					action: 'log',
					event: event_action,
					aid: this.qfa_data['aid'],
					question: question,
					answer: answer,
					reason: this.qfa_data['reason']
				},
				function(result) {
					console.log(result);
				}
			);
		}


	}

	$(document).ready(function() {
		WH.FixFlaggedAnswers.init();
	});

}($));