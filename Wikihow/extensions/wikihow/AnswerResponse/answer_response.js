(function($) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.AnswerResponse = {

		helpful: null,

		init: function() {
			this.helpful = $('#qaar_vote').val();
			this.setHandlers();
			this.logIt('answeremail_helpfulness_votes');
		},

		setHandlers: function() {
			if (this.helpful == 0) {
				//submit button enable/disable check (only for unhelpful since helpful comment is optional)
				$('#qaar_answer').on('keyup', function() {
					if ($(this).val() == '' && !$('#qaar_submit').hasClass('disabled')) {
						$('#qaar_submit').removeClass('primary').addClass('disabled');
					}
					else if ($(this).val() != '' && $('#qaar_submit').hasClass('disabled')) {
						$('#qaar_submit').removeClass('disabled').addClass('primary');
					}
				});
			}
			else {
				if ($('#qaar_is_anon').val() == 1) {
					//only anon
					$('#qaar_anon_checkbox').hide();
					this.anonBoxClick(true);
				}
				else {
					//anon checkbox logic
					$('#qaar_anon').click($.proxy(function() {
						this.anonBoxClick(false);
					},this));
				}
			}

			//form submit
			$('#qaar_submit').click($.proxy(function(button) {
				if ($(button.target).hasClass('disabled')) return false;


				if (this.helpful == 1) {
					this.submitIt();
					this.logIt('answer_thanks_message_submitted');
					this.thanks('qaar_thanks_1');
				}
				else {
					this.logIt('answeremail_helpfulness_comments');
					this.thanks('qaar_thanks');
				}

				return false;
			},this));
		},

		thanks: function(msg) {
			$('#qaar_body').slideUp(function() {
				$('#qaar_body').html(mw.msg(msg)).slideDown();
			});
		},

		logIt: function(event) {
			if (!event) return;
			var eventProps = {
				'category': 'qa',
				'vote': this.helpful == 1 ? 'helpful' : 'unhelpful',
				'submittedTimestamp': $('#qaar_st').val(),
				'emailTimestamp': $('#qaar_et').val(),
				'qa_id': $('#qaar_qa_id').val()
			};

			//let's sanitize the textarea before submitting
			var comment = $("<div/>").html($('#qaar_answer').val()).text();

			if (event == 'answeremail_helpfulness_comments') {
				eventProps['comment'] = comment;
			}
			else if (event == 'answer_thanks_message_submitted') {
				eventProps['message'] = comment;
				if ($('#qaar_anon')) eventProps['send_anonymously'] = $('#qaar_anon').attr('checked');
			}

			WH.maEvent(event, eventProps, false);
		},

		anonBoxClick: function(force_it) {
			var make_anon = force_it == true || $('#qaar_anon').attr('checked');

			if (make_anon) {
				//go Anon
				$('.qaar_user').hide();
				$('.qaar_anon').show();
				$('#qaar_is_anon').val('1');
			}
			else {
				//go User
				$('.qaar_user').show();
				$('.qaar_anon').hide();
				$('#qaar_is_anon').val('0');
			}
		},

		submitIt: function() {
			//sanitize
			var comment = $("<div/>").html($('#qaar_answer').val()).text();

			var data = {
				qa_id: $('#qaar_qa_id').val(),
				comment: comment,
				is_anon: $('#qaar_is_anon').val()
			};

			$.post('/Special:AnswerResponse?a=submit', data);
		}
	}

	$(document).ready(function() {
		WH.AnswerResponse.init();
	});
}($))
