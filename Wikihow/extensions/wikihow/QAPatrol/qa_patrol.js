(function() {
	"use strict";
	window.WH = window.WH || {};
	window.WH.QAPatrol = {

		toolURL: '/Special:QAPatrol',
		question_max: 500,
		answer_max: 1000,
		qap_edited: 0,
		qap_data: {},
		expert_mode: 0,
		top_answerer_mode: 0,

		init: function() {
			//check to see if we're in a special mode
			if ($('#expert_mode').val() == 1) WH.QAPatrol.expert_mode = 1;
			if ($('#top_answerer_mode').val() == 1) WH.QAPatrol.top_answerer_mode = 1;

			WH.QAPatrol.getQA();
			WH.QAPatrol.addHandlers();
		},

		addHandlers: function() {
			$('#qap_btn_skip').click(function() {
				WH.QAPatrol.processing(WH.QAPatrol.skipCallback);
			});

			$('#qap_btn_no').click(function() {
				WH.QAPatrol.hideVoteButtons();
				WH.QAPatrol.vote(0);
			});

			$(document).on('click', '#qap_btn_edit, #qap_question, #qap_answer', function() {
				WH.QAPatrol.editMode();
				$(this).attr('id') == 'qap_answer' ? $('#qap_answer_edit').focus() : $('#qap_question_edit').focus();
			});

			$('#qap_btn_yes').click(function() {
				WH.QAPatrol.hideVoteButtons();
				WH.QAPatrol.vote(1);
			});

			$('#qap_delete_q').click(function() {
				WH.QAPatrol.deleteQuestion();
			});

			//add handlers
			$(document).on('click', '#qap_btn_cancel', function() {
				WH.QAPatrol.cancelEdit();
			});

			$(document).on('click', '#qap_btn_save', function() {
				WH.QAPatrol.processing(WH.QAPatrol.saveEdit);
			});

			$(document).on('click', '#qap_flag_great', function() {
				WH.QAPatrol.flagGreat($(this));
				return false;
			});

			//add the character limit stuff
			this.addKeyHandlers();
		},

		hideVoteButtons: function() {
			$('#qap_btn_yes').hide();
			$('#qap_btn_no').hide();
		},

		showVoteButtons: function() {
			$('#qap_btn_yes').show();
			$('#qap_btn_no').show();
		},

		getQA: function(reset) {
			if (reset) this.viewMode(); //bring back from edit mode
			$('#qap_article_html').remove(); //clear!

			var url = this.toolURL+'?action=getQA';
			if (WH.QAPatrol.expert_mode) url += '&expert=true';
			if (WH.QAPatrol.top_answerer_mode) url += '&ta=true';

			$.getJSON(url, function(data) {
				$('#qap_spinner').fadeOut(function() {
					$('#qap_main').fadeIn();
				});

				//show the edited count
				if (WH.QAPatrol.qap_edited > 0) {
					$('#qap_edited h2').html(WH.QAPatrol.qap_edited);
					$('#qap_edited').show();
				}

				if (data.eoq) {
					$('#qap_article').html('');
					$('#qap_main').html(data.eoq);
					$('#qap_count h3').html(data.remaining);
				}
				else if (data) {
					$('#qap_article').html(data.title).attr('href',data.link);
					$('#qap_question').html(data.question);
					$('#qap_answer').html(data.answer_formatted);
					$('#qap_count h3').html(data.remaining);
					$('#qap_votes_left').html(data.votes_left);
					$('#qap_user_data').html(mw.message('qap_flag_great',data.user_data).text());
					$('#qap_qid').html(mw.message('qap_qid',data.qap_sqid).text());

					// reset our local stash of data
					WH.QAPatrol.qap_data = {
						'submit_time': data.submit_time,
						'patroller_name': data.patroller_name,
						'submitter_name': data.submitter_name,
						'article_id': data.aid,
						'article_title': data.title,
						'qs_id': data.qap_sqid,
						'original_question': data.question,
						'original_answer': data.answer,
						'original_answer_formatted': data.answer_formatted,
						'qap_id': data.qap_id,
						'verifier_id': data.verifier_id
					};

					//grab widget stuff
					if (!WH.QAWidget) {
						mw.loader.load(['ext.wikihow.qa_widget'], null, true);
					}

					//add article html
					var article_html = $('<div id="qap_article_html" />');
					$(article_html).html(data.article);
					$('#bodycontents').after(article_html);

					//show possible duplicate warning?
					if ($('.qa_aq').length >= 5) $('#qap_dup').fadeIn();

					//add question ids to the Q&A section of the article
					var qid_msg = '';
					$('.qa_aq').each(function() {
						qid_msg = mw.message('qap_qid',$(this).attr('data-sqid')).text();
						$(this).find('.qa_q_txt').before('<div class="qap_qa_qid">'+qid_msg+'</div>');
					});
				}
			});
		},

		vote: function(vote) {
			//takes a little bit; let's hide some stuff while processing
			WH.QAPatrol.processing();

			$.getJSON(this.toolURL+'?action=vote&vote='+vote+'&id='+this.qap_data['qap_id'], function(data) {

				//machinify vote events
				//----------------------
				var log_event = '';

				if (!data.power_voter) {
					//only log regular clicks for normal users
					log_event = vote ? 'approve_vote' : 'reject_vote';
					WH.QAPatrol.logIt(log_event);
				}

				//max reached
				if (data.approved) {
					log_event = data.power_voter ? 'approve_without_edit' : 'approve_vote_majority';
					WH.QAPatrol.logIt(log_event);
				}
				else if (data.deleted) {
					log_event = data.power_voter ? 'reject_answer' : 'reject_vote_majority';
					WH.QAPatrol.logIt(log_event);
				}
				//----------------------

				//next...
				WH.QAPatrol.getQA();
				WH.QAPatrol.showVoteButtons();
			});
		},

		editMode: function() {
			//swap instructions
			$('#qap_txt').html(mw.message('qap_txt_edit').text());

			//make question editable
			var edit_ta_q = $('<textarea id="qap_question_edit" class="wh_block qap_edit_ta" />');
			$('#qap_question').replaceWith(edit_ta_q);
			$('#qap_q').hide();
			$(edit_ta_q).html(WH.QAPatrol.qap_data['original_question']);

			//make answer editable
			var edit_ta_a = $('<textarea id="qap_answer_edit" class="wh_block qap_edit_ta" />');
			$('#qap_answer').replaceWith(edit_ta_a);
			$('#qap_a').hide();
			$(edit_ta_a).html(WH.QAPatrol.qap_data['original_answer']);

			//swap buttons
			$('#qap_buttons_main').fadeOut(function() {
				$('#qap_btn_save').show();
				$('#qap_buttons_edit').fadeIn();
			});

			this.newLineCheck();
		},

		viewMode: function() {
			//swap instructions
			$('#qap_txt').html(mw.message('qap_txt').text());

			//make question editable
			var edit_q = $('<div id="qap_question" class="wh_block"></div>');
			$('#qap_question_edit').replaceWith(edit_q);
			$('#qap_q').show();

			//make answer editable
			var edit_a = $('<div id="qap_answer" class="wh_block"></div>');
			$('#qap_answer_edit').replaceWith(edit_a);
			$('#qap_a').show();

			//swap buttons
			$('#qap_buttons_edit').fadeOut(function() {
				$('#qap_buttons_main').fadeIn();
			});
		},

		saveEdit: function() {
			var stuff = {
				'id' : WH.QAPatrol.qap_data['qap_id'],
				'question' : $('#qap_question_edit').val(),
				'answer' : $('#qap_answer_edit').val()
			};
			$.post(WH.QAPatrol.toolURL+'?action=save', stuff, function(data) {

				//up that count
				WH.QAPatrol.qap_edited++;

				//log it
				var log_event = data.approved ? 'approve_with_edit' : 'edit_without_approve';
				WH.QAPatrol.qap_data['edited_question'] = stuff['question'];
				WH.QAPatrol.qap_data['edited_answer'] = stuff['answer'];
				WH.QAPatrol.qap_data['similarity_score'] = data.similarity_score;
				WH.QAPatrol.logIt(log_event);

				WH.QAPatrol.getQA(true);

			},'json');
		},

		cancelEdit: function() {
			var q = WH.QAPatrol.qap_data['original_question'];
			var a = WH.QAPatrol.qap_data['original_answer_formatted'];

			//swap instructions
			$('#qap_txt').html(mw.message('qap_txt').text());

			//make Q&A uneditable
			var view_ta_q = $('<div id="qap_question" class="wh_block" />');
			$('#qap_question_edit').replaceWith(view_ta_q);
			$('#qap_q').show();
			$(view_ta_q).html(q);

			var view_ta_a = $('<div id="qap_answer" class="wh_block" />');
			$('#qap_answer_edit').replaceWith(view_ta_a);
			$('#qap_a').show();
			$(view_ta_a).html(a);

			//swap buttons
			$('#qap_buttons_edit').fadeOut(function() {
				$('#qap_buttons_main').fadeIn();
			});

			//hide any err msg
			$('#qap_err_msg').hide();
		},

		/**
		 * addKeyHandlers()
		 *
		 * while editing we want to watch for:
		 * - too many characters
		 * - line breaks
		 */
		addKeyHandlers: function() {
			//QUESTION
			$(document).on('keyup', '#qap_question_edit', function() {
				//character limit
				if ($(this).val().length > WH.QAPatrol.question_max) {
					$('#qap_q_cl').show();
					$('#qap_buttons_edit').slideUp();
					$(this).addClass('qap_text_err');
				}
				else if ($(this).val().length <= WH.QAPatrol.question_max) {
					$('#qap_q_cl').hide();
					$('#qap_buttons_edit').slideDown();
					$(this).removeClass('qap_text_err');
				}
			});

			//ANSWER
			$(document).on('keyup', '#qap_answer_edit', function() {
				//character limit
				if ($(this).val().length > WH.QAPatrol.answer_max) {
					$('#qap_a_cl').show();
					$('#qap_buttons_edit').slideUp();
					$(this).addClass('qap_text_err');
				}
				else if ($(this).val().length <= WH.QAPatrol.answer_max) {
					$('#qap_a_cl').hide();
					$('#qap_buttons_edit').slideDown();
					$(this).removeClass('qap_text_err');
				}

				//new line check
				WH.QAPatrol.newLineCheck();
			});
		},

		/**
		 * newLineCheck()
		 *
		 * checks for new lines and shows err message if found
		 * (fires on keyup and on edit mode)
		 */
		newLineCheck: function() {
			var a = $('#qap_answer_edit');

			if (a.val().indexOf("\n") >= 0) {
				$('#qap_err_msg').html(mw.message('qap_answer_lf_err').text()).show();
			}
			else {
				$('#qap_err_msg').hide();
			}
		},

		deleteQuestion: function() {
			WH.QAPatrol.processing();
			$.get(this.toolURL+'?action=delete_question&id='+this.qap_data['qap_id'], function() {
				WH.QAPatrol.logIt('delete_question');
				WH.QAPatrol.getQA();
			});
		},

		skipCallback: function() {
			$.get(WH.QAPatrol.toolURL+'?action=skip&id='+WH.QAPatrol.qap_data['qap_id'], function() {
				WH.QAPatrol.logIt('skip');
				WH.QAPatrol.getQA();
			});
		},

		flagGreat: function(obj) {
			WH.QAPatrol.logIt('flag_great');
			$(obj).parent().html(mw.message('qap_flag_thanks').text());
		},

		//hide stuff while processing...
		processing: function(callback) {
			$('#qap_buttons_edit').fadeOut();
			$('#qap_main').fadeOut(function() {
				$('#qap_article').html('');
				$('#qap_article_html').remove();
				$('#qap_dup').hide();
				$('#qap_err_msg').hide();
				$('#qap_spinner').fadeIn(function() {
					if (typeof(callback) == "function") callback();
				});
			});
		},

		logIt: function(action) {
			var event = action == 'flag_great' ? 'qapatrol_great_answer_flag' : 'qapatrol_action';
			var eventProps = $.extend(true, {}, WH.QAPatrol.qap_data); //copy our data array

			eventProps['category'] = 'qa_patrol',
			eventProps['action'] = action;
			delete eventProps['original_answer_formatted'];

			WH.maEvent(event, eventProps, false);
		}
	}

	$(document).ready(function() {
		WH.QAPatrol.init();
	});

})(jQuery);
