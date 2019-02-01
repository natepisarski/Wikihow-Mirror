/*global WH*/
(function($, mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.QAWidget = {
		isUnansweredQuestionsTarget: $('.qa.qa_show_unanswered_questions').length > 0,
		isAdmin: $('#qa_admin').length > 0,
		isEditor: $('#qa_editor').length > 0,
		isStaff: $('#qa_staff').length > 0,
		$questions: $('.qa_aq'),
		getAllQuestions: function() { return $('.qa_aq')},
		getActiveQuestions: function() {return $('.qa_aq').not('.qa_inactive')},
		getUnpatrolledQuestions: function() {return $(".qa_up")},
		$articleQuestions: $('#qa_article_questions'),
		$unpatrolledQuestions: $('#qa_article_unpatrolled'),
		$prompt: $('#qa_prompt'),
		$showMore: $('#qa_show_more_answered'),
		endpoint: '/Special:QA',
		patrolEndpoint: '/Special:QAPatrol',
		maxQuestionsVisible: WH.isMobileDomain ? 5 : 10,
		maxSubmittedQuestionsVisible: 5,
		minSpaces: 2,
		minSubmittedQuestionChars: 20,
		minProposedAnswerChars: 75,
		EVENT_CAT: "qa",
		sectionViewed: false,
		emailRegex: /^(([^<>()[\]\.,;:\s@\"]+(\.[^<>()[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i,
		EVENT_PROPOSED_ANSWER_SUBMISSION: 'qa/proposed_answer_submitted',
		verifiers: null,
		searchList: null,
		offset: 0,
		fo_reg_height: '19px',
		fo_trans_height: '29px',
		afo_reg_height: '20px',
		sqids: [], // Questions the user has answered
		QA_ASKED_QUESTION_MAXLENGTH: 200,
		isUnpatrolledQuestionsTarget: $('#qa_article_unpatrolled').length > 0,
		validator: null,
		tabox_pos_start: 45,
		tabox_pos_end: 55,
		tabox_duration: 150,
		exbox_pos_start: 45,
		exbox_pos_end: 55,
		exbox_duration: 150,
		initial_question_count: 0,

		init: function() {
			this.initListeners();

			this.initial_question_count = this.getAllQuestions().length;

			if (!this.initial_question_count) {
				this.$prompt.html(mw.msg('qa_prompt_first')).show();

				// Hide Answered questions section for non-admins if there aren't any
				if (!this.isAdmin) {
					$('#qa_answered_questions_container').hide();
				}
			}

			if (this.isAdmin && this.verifiers === null) {
				this.loadVerifiers();
			}

			//turn on those not-good-page user links for logged in users
			this.finishUserLinks();

			//this.initSearch();
		},

		expandList: function() {
			this.getActiveQuestions().show();
			this.$showMore.hide();
		},

		editUnpatrolledQuestion: function($li) {
			$li.addClass('qa_editing_up');
			var formData = {
				qa_up_edit_form_question: $li.find('.qa_q_txt').html(),
				qa_up_edit_form_answer: $li.find('.qa_answer').html(),
				qa_up_edit_form_submitter_id: $li.data('submitter_id'),
				selected: function() {
					return this.id == $li.data('verifier_id') ? 'selected' : '';
				}
			};
			var html = WH.QAWidget.getUnpatrolledEditForm(formData);
			$li.find('.qa_li_container').append(html);
		},

		showRemainderUnpatrolled: function() {
			var widget = this;
			$.post(
				this.endpoint,
				{
					a: 'gupqs',
					aid: $('.qa').data('aid'),
					offset: widget.getUnpatrolledQuestions().length,
					limit: 50
				},
				function(upqs) {
					var upqsHtml = '';
					for(var i in upqs) {
						upqsHtml += widget.getUnpatrolledQuestion(upqs[i]);
					}
					widget.$unpatrolledQuestions.append(upqsHtml);
					$("#qa_show_more_unpatrolled").hide();
					widget.$unpatrolledQuestions.removeClass('qa_waittheresmore');
				},
				'json'
			)
		},

		editArticleQuestion: function ($li) {
			$li.addClass('qa_editing_aq');
			var formData = {
				qa_edit_form_inactive_val: $li.data('qa_inactive') ? 'checked' : '',
				qa_edit_form_question: $li.find('.qa_q_txt').html(),
				qa_edit_form_answer: $li.find('.qa_answer').html(),
				qa_edit_form_submitter_id: $li.data('submitter_id'),
				selected: function() {
					return this.id == $li.data('verifier_id') ? 'selected' : '';
				}
			};
			var html = WH.QAWidget.getEditForm(formData);
			$li.find('.qa_li_container').append(html);
		},

		initEditorListeners: function() {
			if (this.isUnansweredQuestionsTarget && !this.isAdmin && !this.isEditor) {
				$('.qa_sq').on('keyup', '.qa_aq_edit_form textarea', function(e) {
					var errorClass = '.' + $(this).attr('class') + '_error';
					if ($(this).attr('maxlength') == $(this).val().length) {
						$(this).siblings(errorClass).first().addClass('active');
					} else {
						$(this).siblings(errorClass).first().removeClass('active');
					}
				});
			}

			$('.qa').on('click', '#qa_edit', $.proxy(function(e) {
				e.preventDefault();
				this.enableEditingUI();
			}, this));

			$('.qa').on('click', '#qa_edit_done', $.proxy(function(e) {
				e.preventDefault();
				this.disableEditingUI();
			}, this));

			$('.qa').on('click', '#qa_show_more_submitted',  $.proxy(function(e) {
				e.preventDefault();
				this.loadSubmittedQuestions();
			}, this));

			$('.qa').on('click', '.qa_edit_submitted',  function(e) {
				e.preventDefault();
				// WH.whEvent(WH.QAWidget.EVENT_CAT, 'click_answer_submitted');
				if (WH.QAWidget.isAdmin) {
					WH.QAWidget.enableEditingUI();
				}

				var $li = $(this).closest('.qa_sq');
				$li.addClass('qa_editing_aq');
				var html = WH.QAWidget.getEditForm({qa_edit_form_question: $li.find('.qa_q_txt').html()});
				$li.find('.qa_li_container').append(html);
			});

			$('.qa').on('click', '.qa_edit_aq', function(e) {
				e.preventDefault();
				WH.whEvent(WH.QAWidget.EVENT_CAT, 'click_edit_answered');
				var $li = $(this).closest('.qa_aq');
				WH.QAWidget.editArticleQuestion($li);
			});

			if(!WH.isMobileDomain && this.isUnpatrolledQuestionsTarget) {
				$('.qa').on('click', '.qa_edit_up', function (e) {
					e.preventDefault();
					var widget = WH.QAWidget;
					var $li = $(this).closest('.qa_up');
					$.post(
						widget.patrolEndpoint,
						{
							action: 'checkout',
							id: $li.data('qapid')
						},
						$.proxy(function (result) {
							if (result.success) {
								widget.editUnpatrolledQuestion($li);
							} else {
								alert(mw.msg("qa_checkout_error"))
							}
						}, this), "json"
					);
				});

				$('.qa').on('click', '.qa_up .qa_up_approve', function (e) {
					e.preventDefault();
					var $li = $(this).closest('.qa_up');
					var widget = WH.QAWidget;

					widget.doUnpatrolledAnswerSubmission($li);
				});

				$('.qa').on('click', '.qa_up_reject', function(e){
					e.preventDefault();
					var $li = $(this).closest('.qa_up');
					var widget = WH.QAWidget;

					widget.rejectUnpatrolledAnswer($li);
				});

				$('.qa').on('click', '.qa_up_delete', function(e){
					e.preventDefault();
					var $li = $(this).closest('.qa_up');
					var widget = WH.QAWidget;

					widget.deleteUnpatrolledAnswer($li);
				});

				$('.qa').on('click', '.qa_up_cancel',  function(e) {
					e.preventDefault();
					var $li = $(this).closest('.qa_up');

					$li.removeClass('qa_editing_up');
					$li.find('.qa_up_edit_form').remove();

					$.post( WH.QAWidget.patrolEndpoint, { action: 'uncheckout', id: $li.data('qapid') } );
				});
			}

			if (WH.isMobileDomain) {

				//(mobile only)
				$('.qa').on('click', '.qa_ignore_submitted',  function(e) {
					e.preventDefault();
					WH.whEvent(WH.QAWidget.EVENT_CAT, 'click_ignore_submitted');
					var widget = WH.QAWidget;
					var $li = $(this).closest('.qa_sq');
					$.post(
						widget.endpoint,
						{
							a: 'sq_ignore',
							sqid: $li.data('sqid')
						},
						$.proxy(function(result) {
							$li.slideUp();
						}, this)
					);
				});

				$('.qa').on('click', '.qa_flag_submitted',  function(e) {
					e.preventDefault();
					WH.whEvent(WH.QAWidget.EVENT_CAT, 'click_flag_submitted');
					var widget = WH.QAWidget;
					var $li = $(this).closest('.qa_sq');
					$.post(
						widget.endpoint,
						{
							a: 'sq_flag',
							sqid: $li.data('sqid')
						},
						$.proxy(function(result) {
							$li.find('.qa_li_container').html(mw.msg('qa_flagged_confirmation'));
						}, this)
					);
				});
			}
			else {

				//(desktop only)
				//*** Flag as... pop-up ***
				$('.qa').on('mouseenter', '.qa_ignore_submitted, .qa_flag_submitted',  function() {
					WH.QAWidget.popFlagOptions($(this));
				})
				.on('mouseleave', '.qa_ignore_submitted, .qa_flag_submitted', function() {
					WH.QAWidget.hideFlagOptions();
				})
				.on('click', '.qa_ignore_submitted, .qa_flag_submitted', function() {
					return false;
				});

				$("#qa_flag_options").hover(function() {
					$(this)
						.stop(true)
						.fadeIn({queue: false, duration: 150})
						.animate({ bottom: WH.QAWidget.fo_reg_height, opacity: 1 }, 80);
				}, function() {
					$(this)
						.fadeOut({queue: false, duration: 150})
						.animate({ bottom: WH.QAWidget.fo_trans_height }, 150);
				});

				$('#qa_flag_options a').click(function(e){
					e.preventDefault();
					WH.QAWidget.submitFlagOption($(this));
				});
				//^^^ Flag as... pop-up ^^^
			}

			$('.qa').on('click', '.qa_aq_cancel',  function(e) {
				e.preventDefault();
				var $li = $(this).closest('.qa_aq, .qa_sq');

				// If it's in the Curated Questions section and hasn't been submitted
				// then remove the blank article question
				if (!$li.data('aqid') && $li.hasClass('qa_aq')) {
					$li.remove();
				} else {
					$li.removeClass('qa_editing_aq');
					$li.find('.qa_aq_edit_form').remove();
				}
			});

			$('.qa').on('click', '.qa_sq .qa_aq_submit',  function(e) {
				e.preventDefault();
				var $li = $(this).closest('.qa_sq');
				var widget = WH.QAWidget;

				//move the flag options box away so we don't lose it on a save
				$('#qa_submitted_questions').before($('#qa_flag_options'));
				if (widget.isAdmin) {
					widget.doArticleQuestionSubmission($li);
				} else {
					widget.doProposedAnswerSubmission($li);
				}
			});

			$('.qa').on('click', '.qa_aq .qa_aq_submit',  function(e) {
				e.preventDefault();
				var $li = $(this).closest('.qa_aq');
				WH.QAWidget.doArticleQuestionSubmission($li);
			});

			$('.qa').on('click', '.qa_aq_delete',  function(e) {
				e.preventDefault();
				var $li = $(this).closest('.qa_aq');
				$li.addClass('qa_deleting');

				var widget = WH.QAWidget;
				var data = {
					a: 'aq_delete',
					sqid: $li.data('sqid'),
					aqid: $li.data('aqid'),
					cqid: $li.data('cqid'),
					caid: $li.data('caid'),
					aid: mw.config.get('wgArticleId')
				};

				$.post(
					widget.endpoint,
					data,
					$.proxy(function(result) {
						$li.removeClass('qa_deleting');
						if (result.success) {
							$li.slideUp('fast', function(){$li.remove()});
						} else {
							$(this).siblings('.qa_aq_edit_form_status').html(result.msg).slideDown();
						}
					}, this),
					'json'
				);

			});

			$('.qa').on('click', '#qa_add_curated_question', $.proxy(function(e) {
				e.preventDefault();
				var aq = this.enableEditing({});
				var newLi = this.getArticleQuestion(aq);
				this.$articleQuestions.prepend(newLi);
				this.$articleQuestions.find('.qa_edit_aq:first').click();
			}, this));

			if (!WH.isMobileDomain) {
				$('#qa')
					.on('mouseenter mouseleave', '.qa_expert_area', function(e) {
						WH.QAWidget.popExpertInfo($(this).parent().find('.qa_user_hover'), e.type);
					});
			}

			//-- Top Answerers --
			if (WH.isMobileDomain) {
				$('#qa').on('click', '.qa_ta_area', function() {
					WH.QAWidget.topAnswererBox($(this));
				});
			}
			else {
				$('#qa')
					.on('mouseenter mouseleave', '.qa_ta_area', function(e) {
						WH.QAWidget.topAnswererBox($(this).parent(), e.type);
					});
			}
			//--------------------

			if(!WH.isMobileDomain && this.isUnpatrolledQuestionsTarget) {
				$('#qa').on('click', '#qa_show_more_unpatrolled', function(e) {
					e.preventDefault();
					WH.QAWidget.showRemainderUnpatrolled();
				});
			}
		},

		rejectUnpatrolledAnswer: function($li) {
			$.getJSON(this.patrolEndpoint+'?action=vote&vote=0&id='+$li.data('qapid'), $.proxy(function(result) {
				if (result.deleted) {
					$li.slideUp('fast', function(){$li.remove()});
				}
			}, this));
			WH.QAWidget.logPatrolEvent('reject_answer', $li, {});
		},

		deleteUnpatrolledAnswer: function($li) {
			$.get(this.patrolEndpoint+'?action=delete_question&id='+$li.data('qapid'), $.proxy(function(result) {
				$li.slideUp('fast', function(){$li.remove()});
			}, this));
			WH.QAWidget.logPatrolEvent('delete_question', $li, {});
		},

		doUnpatrolledAnswerSubmission: function($li) {
			var stuff = {
				'id' : $li.data('qapid'),
				'question' : $li.find('.qa_cq_text').val(),
				'answer' : $li.find('.qa_ca_text').val(),
				'expert' : $li.find('.qa_edit_form_verifier').find(":selected").val(),
				'remove_submitter': $li.find('#qa_aq_remove_submitter').is(':checked') ? 1 : 0,
				'from_article' : true
			};

			//validate answer
			var answerValid, answerReason;
			answerValid = this.isValidProposedAnswer(stuff.answer);

			if (!answerValid) {
				answerReason = this.validator.getFailedRules();
				if (answerReason.indexOf(this.validator.short_rule) != -1) {
					$li.find(".qa_ca_text_error2").show();
				}
				else if (answerReason.indexOf(this.validator.url_rule) != -1) {
					$li.find(".qa_ca_text_error3").show();
				}
				else if (answerReason.indexOf(this.validator.phone_rule) != -1) {
					$li.find(".qa_ca_text_error_phone").show();
				}
				return;
			}

			if(stuff.question.length > 0 && stuff.answer.length > 0) {
				$.post(this.patrolEndpoint + '?action=save', stuff, $.proxy(function (data) {
					if ($li.hasClass('qa_up') && data.approved == 1) {
						$li.slideUp('fast', function () {
							$(this).remove()
						});
						WH.QAWidget.logPatrolEvent('approve_with_edit', $li, {'similarity_score': data.similarity_score});
						WH.QAWidget.addArticleQuestion(data.result.aq);
					}
				}), 'json');
			}
		},

		doProposedAnswerSubmission: function($li) {
			var widget = WH.QAWidget;

			var data = {
				a: 'pa',
				aid: mw.config.get('wgArticleId'),
				sqid: $li.data('sqid'),
				question: $li.find('.qa_cq_text').val(),
				answer: $li.find('.qa_ca_text').val(),
				email:  $li.find('.qa_pa_email').val(),
				submitter_user_id: 1 //triggers server-side userid grab
			};

			$(".qa_ca_text_error2").hide();

			// All users need validation on answers
			// if ($('.qa').data('few_contribs')) {
				// Answer to a proposed answer submission must be valid.  If it isn't, discard the submission and
				// display a success message anyway as if the answer successfully submitted.
				var answerValid, questionValid, answerReason;
				answerValid = this.isValidProposedAnswer(data.answer);

				if(answerValid) {
					questionValid = this.isValidProposedAnswerQuestion(data.question);
				} else {
					answerReason = this.validator.getFailedRules();
					if (answerReason.indexOf(this.validator.short_rule) != -1) {
						$li.find(".qa_ca_text_error2").show();
					}
					else if (answerReason.indexOf(this.validator.url_rule) != -1) {
						$li.find(".qa_ca_text_error3").show();
					}
					else if (answerReason.indexOf(this.validator.phone_rule) != -1) {
						$li.find(".qa_ca_text_error_phone").show();
					}
					return;
				}

				if (!questionValid) {
					widget.showAnswerConfirmation($li);
					return;
				}
			// }

			if ( data.question.length && data.answer.length ) {
				$li.addClass( 'qa_saving' );
				$.post(
					widget.endpoint,
					data,
					function ( result ) {
						$.when( WH.social.fb(), WH.social.gplus() ).then( function () {
							$li.removeClass( 'qa_saving' );
							widget.sqids.push( $li.data( 'sqid' ) );
							if ( result.isAnon ) {
								widget.showSocialLoginForm( $li );
							} else {
								widget.showAnswerConfirmation( $li );
							}
							if ( !result.userBlocked ) {
								WH.whEvent( WH.QAWidget.EVENT_CAT, 'proposed_answer_submission' );
								$.publish( WH.QAWidget.EVENT_PROPOSED_ANSWER_SUBMISSION );
							}
						},
						function() {
							// fail case
							$li.removeClass( 'qa_saving' );
							widget.sqids.push( $li.data( 'sqid' ) );
							var html = Mustache.render(unescape($('#qa_social_login_form').html()), {
								qa_thanks_for_answer: mw.msg('qa_thanks_for_answer')
							});
							$li.find('.qa_li_container').html(widget.escapeHtml(html));
							$li.find('.qa_li_container').find('.qa_social_login_body').remove();
							$li.find('.qa_li_container').find('.qa_social_login_button_container').remove();
							if ( !result.userBlocked ) {
								WH.whEvent( WH.QAWidget.EVENT_CAT, 'proposed_answer_submission' );
								$.publish( WH.QAWidget.EVENT_PROPOSED_ANSWER_SUBMISSION );
							}
						});
					},
					'json'
				);
			}
		},

		doArticleQuestionSubmission: function($li) {
			var widget = WH.QAWidget;

			var data = {
				a: 'aq',
				aid: mw.config.get('wgArticleId'),
				sqid: $li.data('sqid'),
				aqid: $li.data('aqid'),
				cqid: $li.data('cqid'),
				caid: $li.data('caid'),
				question: $li.find('.qa_cq_text').val(),
				answer: $li.find('.qa_ca_text').val(),
				remove_submitter: $li.find('#qa_aq_remove_submitter').is(':checked') ? 1 : 0,
				inactive: $li.find('#qa_aq_inactive').is(':checked') ? 1 : 0,
				vid: $li.find('.qa_edit_form_verifier').find(":selected").val()
			};

			//don't validate stuff if it's being marked inactive
			if (!data.inactive) {
				// Answer to a proposed answer submission must be valid.  If it isn't, discard the submission and
				// display a success message anyway as if the answer successfully submitted.
				var answerValid, questionValid, answerReason;
				answerValid = this.isValidProposedAnswer(data.answer);

				if(answerValid) {
					questionValid = this.isValidProposedAnswerQuestion(data.question);
				} else {
					answerReason = this.validator.getFailedRules();
					if (answerReason.indexOf(this.validator.short_rule) != -1) {
						$li.find(".qa_ca_text_error2").show();
					}
					else if (answerReason.indexOf(this.validator.url_rule) != -1) {
						$li.find(".qa_ca_text_error3").show();
					}
					else if (answerReason.indexOf(this.validator.phone_rule) != -1) {
						$li.find(".qa_ca_text_error_phone").show();
					}
					return;
				}

				if (!questionValid) {
					widget.showAnswerConfirmation($li);
					return;
				}
			}

			if (data.question.length && data.answer.length) {
				$li.addClass('qa_saving');

				$.post(
					widget.endpoint,
					data,
					$.proxy(function(result) {
						$li.removeClass('qa_saving');
						if (result.success) {
							result.aq = widget.enableEditing(result.aq);
							var eventAction = "";
							var eventProps = {
								'category': WH.QAWidget.EVENT_CAT,
								'label': result.aq.id,
								'article_name': mw.config.get('wgPageName'),
								'question_old': $li.find('.qa_q_txt').html(),
								'question_new': data.question,
								'answer_new': data.answer
							};

							if ($li.hasClass('qa_sq')) {
								$li.slideUp('fast', function() {$(this).remove()});
								widget.addArticleQuestion(result.aq);
								eventAction = 'edit_submitted';
							} else {
								eventAction = 'edit_curated';
								$li.replaceWith(widget.getArticleQuestion(result.aq));

								eventProps['answer_old'] = $li.find('.qa_answer').html();
							}

							WH.maEvent(eventAction, eventProps, false);

						} else {
							$(this).siblings('.qa_aq_edit_form_status').html(result.msg).slideDown();
						}
					}, this),
					'json'
				);
			}
		},

		enableEditing: function(aq) {
			aq.qa_admin = true;
			aq.qa_edit = mw.msg('qa_edit');
			aq.show_editor_tools = true;
			return aq;
		},

		addArticleQuestion: function(aq) {
			var aqHtml = this.getArticleQuestion(aq);
			this.$articleQuestions.prepend(aqHtml);
		},

		getArticleQuestion: function(aq) {
			// Add thumbs up/down template
			var partials;
			var msgs;
			var aqTemplateId;
			if (WH.isMobileDomain) {
				aq['qa_desktop'] = false;
				partials = {thumbs_up_down: this.escapeHtml($('#qa_thumbs_up_down').html())};
			} else {
				aq['qa_desktop'] = true;
				partials = {thumbs_qa_widget: this.escapeHtml($('#qa_thumbs_qa_widget').html())};
			}

			aq['qa_editor'] = this.isEditor;
			aq['qa_anon'] = !mw.config.get('wgUserId');

			aqTemplateId = '#qa_article_question_item';
			msgs = [
				'thumbs_response',
				'qa_thumbs_yes',
				'qa_thumbs_no',
				'qa_thumbs_nohelp',
				'qa_thumbs_help',
				'qa_target_page',
				'thumbs_default_prompt',
				'qa_answered_by',
				'qa_flag_duplicate',
				'ta_answers_label',
				'ta_label',
				'ta_subcats_intro',
				'ta_subcats_outro',
				'qa_question_label'
			];

			aq = $.extend(aq, this.getTemplateVars(msgs));

			if (this.isEditor) {
				aq = this.enableEditing(aq);
			}

			return this.escapeHtml(Mustache.render(this.escapeHtml($(aqTemplateId).html()), aq, partials));
		},

		getTemplateVars: function(msgs) {
			var data = {};
			for (var i in msgs) {
				data[msgs[i]] = mw.msg(msgs[i]);
			}
			return data;
		},

		getEditForm: function(formData) {
			var defaultData = {
				qa_pa_email_placeholder: mw.msg('qa_pa_email_placeholder'),
				qa_edit_form_submit: mw.msg('qa_edit_form_submit'),
				qa_edit_form_delete: mw.msg('qa_edit_form_delete'),
				qa_edit_form_cancel: mw.msg('qa_edit_form_cancel'),
				qa_edit_form_show_remove_submitter: formData.qa_edit_form_submitter_id > 0,
				qa_edit_form_remove_submitter: mw.msg('qa_edit_form_remove_submitter'),
				qa_edit_form_inactive: mw.msg('qa_edit_form_inactive'),
				qa_edit_form_question_placeholder: mw.msg('qa_edit_form_question_placeholder'),
				qa_edit_form_answer_placeholder: mw.msg('qa_edit_form_answer_placeholder'),
				qa_cq_text_error: mw.msg('qa_cq_text_error'),
				qa_ca_text_error: mw.msg('qa_ca_text_error'),
				qa_ca_text_min: mw.msg('qa_ca_text_min'),
				qa_ca_error_url: mw.msg('qa_ca_error_url'),
				qa_ca_error_phone: mw.msg('qa_ca_error_phone'),
				qa_edit_form_verifier_label: mw.msg('qa_edit_form_verifier_label'),
				qa_edit_form_verifiers: this.verifiers,
				qa_admin: this.isAdmin,
				qa_editor: this.isEditor
			};
			defaultData = $.extend(defaultData, formData);
			defaultData.qa_edit_form_inactive_val = defaultData.qa_edit_form_inactive_val ? 'checked' : '';

			var html = this.escapeHtml(Mustache.render(unescape($('#qa_question_edit_form').html()), defaultData));
			if (this.isUnansweredQuestionsTarget && !this.isAdmin && !this.isEditor) {
				var wrappedHtml = $('<div>').html(html);
				wrappedHtml.find('.qa_cq_text').attr('maxlength', 500);
				wrappedHtml.find('.qa_ca_text').attr('maxlength', 700);
				html = wrappedHtml.html();
			}

			return html;
		},

		getUnpatrolledEditForm: function(formData) {
			var defaultData = {
				qa_pa_email_placeholder: mw.msg('qa_pa_email_placeholder'),
				qa_up_edit_form_approve: mw.msg('qa_up_edit_form_approve'),
				qa_up_edit_form_delete: mw.msg('qa_up_edit_form_delete'),
				qa_edit_form_cancel: mw.msg('qa_edit_form_cancel'),
				qa_up_edit_form_reject: mw.msg('qa_up_edit_form_reject'),
				qa_edit_form_show_remove_submitter: formData.qa_up_edit_form_submitter_id > 0,
				qa_edit_form_remove_submitter: mw.msg('qa_edit_form_remove_submitter'),
				qa_edit_form_inactive: mw.msg('qa_edit_form_inactive'),
				qa_edit_form_question_placeholder: mw.msg('qa_edit_form_question_placeholder'),
				qa_edit_form_answer_placeholder: mw.msg('qa_edit_form_answer_placeholder'),
				qa_cq_text_error: mw.msg('qa_cq_text_error'),
				qa_ca_text_error: mw.msg('qa_ca_text_error'),
				qa_ca_text_min: mw.msg('qa_ca_text_min'),
				qa_ca_error_url: mw.msg('qa_ca_error_url'),
				qa_ca_error_phone: mw.msg('qa_ca_error_phone'),
				qa_edit_form_verifier_label: mw.msg('qa_edit_form_verifier_label'),
				qa_edit_form_verifiers: this.verifiers,
				qa_admin: this.isAdmin,
				qa_editor: this.isEditor
			};
			defaultData = $.extend(defaultData, formData);

			var html = this.escapeHtml(Mustache.render(unescape($('#qa_unpatrolled_edit_form').html()), defaultData));

			return html;
		},

		getUnpatrolledQuestion: function(upq) {
			var msgs;
			var upqTemplateId;

			upq['qa_editor'] = this.isEditor;

			upqTemplateId = '#qa_patrol_item';
			msgs = [
				'qa_edit'
			];

			upq = $.extend(upq, this.getTemplateVars(msgs));

			return this.escapeHtml(Mustache.render(this.escapeHtml($(upqTemplateId).html()), upq));
		},

		initSearch: function() {
			var searchList = WH.QAWidget.searchList;
			if (searchList === null && !WH.isMobileDomain && $('.qa').data('search_enabled')) {
				mw.loader.using('wikihow.common.listjs', $.proxy(function() {
					$('#qa').addClass('qa_search');
					this.expandList();
					var options = {
						valueNames: ['question', 'answer'],
						listClass: 'qa_aqs',
						searchClass: 'qa_aq_search',
						indexAsync: false,
						// page: 10
					}
					this.searchList = searchList = new List('qa_answered_questions_container', options);
				}, this));
			}
		},

		initListeners: function() {
			if (WH.isMobileDomain) {
				$('#qa').on('click', '#qa_asked_question', function(e) {
					e.preventDefault();
					$('#qa_email, #qa_email_prompt, #qa_asked_count').show();
				});
			} else {
				$('#qa').on('keydown', '#qa_asked_question', $.proxy($.throttle(250, function(e) {
					$('#qa_email, #qa_email_prompt, #qa_asked_count').show();
				})));
			}

			//'n characters left' logic
			$('#qa_asked_question').on('keyup', function() {
				var remaining = WH.QAWidget.QA_ASKED_QUESTION_MAXLENGTH - $(this).val().length;
				$('#qa_asked_count').html(mw.msg('qa_asked_count',remaining));
				remaining == 0 ? $('#qa_asked_count').addClass('qa_asked_zero') : $('#qa_asked_count').removeClass('qa_asked_zero');
			});

			// Anon contributers of proposed answers should be prompted for their email address
			// upon writing an answer to a submitted question
			if (this.isUnansweredQuestionsTarget && !mw.config.get('wgUserId')) {
				$('#qa').on('keydown', '.qa_ca_text', $.throttle(250, function(e) {
					$(this).siblings('.qa_pa_email').show();
				}));
			}

			if (this.isUnansweredQuestionsTarget) {
				this.initEditorListeners();
			}

			$.subscribe(WH.ThumbsUpDown.VOTE_UP_EVENT, function(e, elem) {
				var aqid = $(elem).closest('.qa_aq').data('aqid');
				WH.QAWidget.vote(WH.ThumbsUpDown.VOTE_UP_EVENT, aqid);
			});

			$.subscribe(WH.ThumbsUpDown.VOTE_DOWN_EVENT, function(e, elem) {
				var aqid = $(elem).closest('.qa_aq').data('aqid');
				WH.QAWidget.vote(WH.ThumbsUpDown.VOTE_DOWN_EVENT, aqid);
			});

			$('#qa').on('click', '#qa_show_more_answered', $.proxy(function(e) {
				e.preventDefault();
				this.onShowMoreAnswered();
			}, this));

			$('#qa').on('click', '#qa_submit_button', $.proxy(function(e) {
				e.preventDefault();
				this.onSubmit();
			}, this));

			//(desktop only)
			//*** Flag as... pop-up for answered questions***
			$('.qa').on('mouseenter', '.qa_ignore_answered',  function() {
				WH.QAWidget.popAnswerFlagOptions($(this));
			})
				.on('mouseleave', '.qa_ignore_answered', function() {
					WH.QAWidget.hideAnswerFlagOptions();
				})
				.on('click', '.qa_ignore_answered', function() {
					return false;
				});

			$("#qa_answer_flag_options").hover(function() {
				$(this)
					.stop(true)
					.fadeIn({queue: false, duration: 150})
					.animate({ bottom: WH.QAWidget.afo_reg_height, opacity: 1 }, 80);
			}, function() {
				$(this)
					.fadeOut({queue: false, duration: 150})
					.animate({ bottom: WH.QAWidget.fo_trans_height }, 150);
			});

			$('#qa_answer_flag_options a').click(function(e){
				e.preventDefault();
				WH.QAWidget.submitAnswerFlagOption($(this));
			});
			//^^^ Flag as... pop-up ^^^

			$(window).scroll($.proxy($.throttle(250, function() {
				if (!this.sectionViewed && this.isVisible('#qa')) {
					this.sectionViewed = true;
				}
			}), this));
		},

		vote: function(type, aqid) {
			var voteType = type == WH.ThumbsUpDown.VOTE_UP_EVENT ? 'up' : 'down';
			$.post(
				this.endpoint,
				{
					a: 'vote',
					aqid: aqid,
					type: voteType
				}
			);
		},

		onShowMoreAnswered: function() {
			var widget = this;
			var offset = this.getAllQuestions().length;

			if (this.$articleQuestions.hasClass('qa_fresh')) offset += this.initial_question_count;

			$.post(
				this.endpoint,
				{
					a: 'gaqs',
					aid: $('.qa').data('aid'),
					offset: offset,
					limit: this.maxQuestionsVisible
				},
				function(aqs) {
					var aqHtml = '';
					for(var i in aqs) {
						aqHtml += widget.getArticleQuestion(aqs[i]);
					}
					widget.$articleQuestions.append(aqHtml);
					if (aqs.length < widget.maxQuestionsVisible) {
						widget.$showMore.hide();
						widget.$articleQuestions.removeClass('qa_waittheresmore');
					}
				},
				'json'
			)
		},

		onSubmit: function() {
			var questionText = $('#qa_asked_question').val();

			if (questionText.length) {

				//don't need this anymore...
				this.hideForm();

				if (this.isValidSubmittedQuestion(questionText)) {
					WH.whEvent(this.EVENT_CAT, "submit");
					$.post(
						this.endpoint,
						{
							a: 'sq',
							aid: mw.config.get('wgArticleId'),
							q: questionText,
							email: this.getEmail()
						},
						function(result) {
							$('#qa_submitted_question_form').append(result);
						}
					);
					return;
				}

				//append default response
				$('#qa_submitted_question_form').append(mw.msg('qa_submitted'));
			}
		},

		getEmail: function() {
			var email = $('#qa_email').val();
		 	if (!this.emailRegex.test(email)) {
				email = null;
			}
			return email;
		},

		isValidSubmittedQuestion: function(question) {
			var isValid = true;

			var config = {
				minspaces: this.minSpaces,
				minlength: this.minSubmittedQuestionChars,
				email: true,
				phone: true,
				url: true
			};

			var validator = this.validator = new WH.StringValidator(config);
			if(!validator.validate(question)) {
				isValid = false;
			}

			return isValid;
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
				WH.whEvent(WH.QAWidget.EVENT_CAT, 'proposed_answer_question_discarded', rules.join(","), question);
			}

			return isValid;
		},

		isValidProposedAnswer: function(answer) {
			var isValid = true;

			if (WH.QAWidget.isStaff) {
				var config = {
					minlength: this.minProposedAnswerChars,
					email: true
				};
			}
			else {
				var config = {
					minlength: this.minProposedAnswerChars,
					email: true,
					phone: true,
					url: true
				};
			}

			var validator = this.validator = new WH.StringValidator(config);
			isValid = validator.validate(answer);

			if (!isValid) {
				var rules = validator.getFailedRules();
				WH.whEvent(WH.QAWidget.EVENT_CAT, 'proposed_answer_discarded', rules.join(","), answer);
			}

			return isValid;
		},

		hideForm: function() {
			$('#qa_email, #qa_email_prompt, #qa_submit_button, #qa_asked_question, #qa_asked_count').hide();
		},

		isVisible: function(elem) {
			var docViewTop = $(window).scrollTop();
			var docViewBottom = docViewTop + $(window).height();

			var elemTop = $(elem).offset().top;
			var elemBottom = elemTop + $(elem).height();

			return ((elemBottom >= docViewTop) && (elemTop <= docViewBottom)
				|| (elemBottom <= docViewBottom) &&  (elemTop >= docViewTop));
		},

		enableEditingUI: function () {
			$('.qa').addClass('qa_editing');
		},

		loadVerifiers: function() {
			$.post(
				this.endpoint,
				{a: 'get_verifiers'},
				function(verifiers) {
					WH.QAWidget.verifiers = verifiers;
				},
				'json'
			)
		},

		disableEditingUI: function() {
			$('.qa').removeClass('qa_editing');
			$('.qa_aq_cancel').click(); //close all forms
			location.hash = '';
			location.hash = '#Questions_and_Answers_sub';
		},

		loadSubmittedQuestions: function() {
			var lastSqid = $('#qa_show_more_submitted').attr('last_sqid') || $('.qa_sq').last().data('sqid');
			$('#qa_submitted_spinner').show();
			$.post(
				this.endpoint,
				{
					a: 'get_submitted_questions',
					aid: mw.config.get('wgArticleId'),
					last_sqid: lastSqid
				},
				function(sqs) {
					$('#qa_submitted_spinner').hide();
					var lastSqid = $('#qa_show_more_submitted').attr('last_sqid');
					if (lastSqid && !sqs) {
						$('#qa_show_more_submitted').hide();
					} else if (!lastSqid && !sqs) {
						$('#qa_submitted_link').replaceWith(mw.msg('qa_none_submitted')).show();
					} else {
						$('#qa_show_more_submitted').show();
					}

					var sqsHtml = '';
					$.each(sqs, function(i, sq) {
						sq.qa_curate =  WH.isMobileDomain ?
							mw.msg('qa_curate_mobile') : mw.msg('qa_curate')
						sq.qa_ignore = mw.msg('qa_ignore');
						sq.qa_flag = mw.msg('qa_flag');
						sq.qa_desktop = !WH.isMobileDomain;

						sqsHtml += Mustache.render(unescape($('#qa_submitted_question_item').html()), sq);
					});

					if (sqs && sqs.length && sqs.length == WH.QAWidget.maxSubmittedQuestionsVisible) {
						var lastSqid = sqs[sqs.length - 1].id;
						$('#qa_show_more_submitted').attr('last_sqid', lastSqid);
						$('#qa_submitted_questions').show();
					} else {
						$('#qa_show_more_submitted').hide();
					}

					$('#qa_submitted_questions').append(WH.QAWidget.escapeHtml(sqsHtml));
				},
				'json'

			)
		},

		escapeHtml: function (htmlString) {
			return $('<textarea/>').html(htmlString).text();
		},

		/**
		 * popExpertInfo()
		 * - show/hide the hover box for experts
		 *
		 * @param obj = the hover box
		 * @param e = (string) mouseenter/mouseleave
		 */
		popExpertInfo: function (obj, e) {
			var pos_start = this.calcMarginTop(obj, this.exbox_pos_start);
			var pos_end = this.calcMarginTop(obj, this.exbox_pos_end);

			if (e == 'mouseenter') {
				if (!$(obj).is(':visible')) $(obj).css('marginTop', pos_end);

				$(obj)
					.stop(true)
					.fadeIn({queue: false, duration: this.exbox_duration})
					.animate({ marginTop: pos_start, opacity: 1 }, this.exbox_duration);
			}
			else {
				$(obj)
					.animate({ marginTop: pos_end }, this.exbox_duration)
					.fadeOut({queue: false, duration: this.exbox_duration});
			}
		},

		popFlagOptions: function (flag) {
			var box = $('#qa_flag_options');
			var left_pos = $(flag).position().left;

			$(flag).before(box);
			$(box)
				.css('left', left_pos - 30)
				.fadeIn({queue: false, duration: 150})
				.animate({ bottom: WH.QAWidget.fo_reg_height, opacity: 1 }, 150);

			//track what kind of flag this is
			$(box).data('type', $(flag).data('type'));
		},

		hideFlagOptions: function () {
			var box = $('#qa_flag_options');
			$(box)
				.fadeOut({queue: false, duration: 150})
				.animate({ bottom: WH.QAWidget.fo_trans_height }, 150);
		},

		submitFlagOption: function (obj) {
			var widget = WH.QAWidget;
			var option = $(obj).data('option');
			var $li = $(obj).closest('.qa_sq');
			var type = $('#qa_flag_options').data('type');

			$.post(
					widget.endpoint,
					{
						a: type,
						sqid: $li.data('sqid')
					},
					$.proxy(function(result) {
						$li.slideUp();
					}, this)
			);

			//log it
			var uid = mw.config.get('wgUserId');
			var event = type == 'sq_ignore' ? 'click_ignore_submitted' : 'click_flag_submitted';
			var eventProps = {
				'category': widget.EVENT_CAT,
				'option': option,
				'qs_id': $li.data('sqid'),
				'user_id': uid != null ? uid : 0,
				'visiter_id': $.cookie('whv'),
				'question': $li.find('.qa_q_txt').html(),
				'article_id': mw.config.get('wgArticleId'),
				'article_name': mw.config.get('wgPageName')
			};
			WH.maEvent(event, eventProps, false);
		},

		popAnswerFlagOptions: function (flag) {
			var box = $('#qa_answer_flag_options');
			var left_pos = $(flag).position().left;

			$(flag).before(box);
			$(box)
				.css('left',left_pos - 30)
				.fadeIn({queue: false, duration: 150})
				.animate({ bottom: WH.QAWidget.afo_reg_height, opacity: 1 }, 150);

			//track what kind of flag this is
			$(box).data('type', $(flag).data('type'));
		},

		hideAnswerFlagOptions: function () {
			var box = $('#qa_answer_flag_options');
			$(box)
				.fadeOut({queue: false, duration: 150})
				.animate({ bottom: WH.QAWidget.fo_trans_height }, 150);
		},

		submitAnswerFlagOption: function (obj) {
			var widget = WH.QAWidget;
			var option = $(obj).html();
			var $li = $(obj).closest('.qa_aq');
			var $flagLink = $(obj).closest(".qa_answer_footer").find(".qa_ignore_answered");
			var $thanksLink = $flagLink.siblings(".qa_ignore_answered_thanks");
			var qa_id = $li.data('aqid');
			var question = $li.find('.qa_q_txt').html();
			var answer = $li.find('.qa_answer').html();
			var expert = $li.data('verifier_id') > 0;

			$.post(
				widget.endpoint,
				{
					a: 'aq_flag',
					aq_id: qa_id,
					reason: option,
					expert: expert
				},
				$.proxy(function(result) {
					var qfa_id = result.saved ? result.qfa_id : 0;
					//get fancy with "Incorrect" or "Other"
					if (option == mw.msg('qa_afo_incorrect') || option == mw.msg('qa_afo_other')) {
						widget.popFlagAsDetailsModal(option, qfa_id, question, answer);
					}
				}, this),
				'json'
			);

			$flagLink.hide();
			widget.hideAnswerFlagOptions();
			$thanksLink.css('display','inline-block');

		},

		/**
		 * topAnswererBox()
		 * - grabs data for top answerer box (if needed) & then fires the show/hide function
		 *
		 * @param obj = user name area
		 * @param e = (string) mouseenter/mouseleave/null
		 */
		topAnswererBox: function(obj, e) {
			if (WH.isAltDomain)
				return;

			var tabox = WH.isMobileDomain ? $(obj).find('.qa_ta_mobile_extra') : $(obj).find('.qa_ta_area .hint_box');
			var show = '';

			if (WH.isMobileDomain)
				show = !$(tabox).is(':visible'); //base it on if the box is showing or not
			else
				show = e == 'mouseenter'; //base it on the event type

			if (show && $(tabox).html() == '') {
				//showing the box AND we haven't already loaded it? load it
				var $li = $(obj).closest('.qa_block');
				var the_url = this.endpoint+'?a=get_ta_data&user_id='+$li.data('submitter_id')+'&aid='+wgArticleId;

				//load up the data
				$.ajax({
					cache: true,
					url: the_url,
					dataType: "json",
					success: $.proxy(function(data) {
						if (data) {
							var username = data.ta_user_real_name ? data.ta_user_real_name : data.ta_user_name;
							var html = Mustache.render(unescape($('#top_answerers_qa_widget').html()), {
								userLink: 				data.ta_user_link,
								userName: 				username,
								answersCount: 		data.ta_answers_live_count,
								topCats: 					data.ta_top_cats,
								ta_image: 				data.ta_user_image,
								ta_label: 				mw.msg('ta_label'),
								ta_answers_label: mw.msg('ta_answers_label'),
								ta_subcats_intro: mw.msg('ta_subcats_intro'),
								ta_subcats_outro: mw.msg('ta_subcats_outro')
							});

							$(tabox).html(this.escapeHtml(html)).promise().done($.proxy(function() {
								//only show when we're done filling the box
								this.popTopAnswererBox(tabox, show);
							},this));
						}
					},this)
				});
			}
			else {
				//showing a filled box or hiding it...
				this.popTopAnswererBox(tabox, show);
			}
		},

		/**
		 * popTopAnswererBox()
		 * - shows/hides top answerer dialog box
		 *
		 * @param tabox = the hover box
		 * @param show = (boolean)
		 */
		popTopAnswererBox: function(tabox, show) {
			var pos_start = this.calcMarginTop(tabox, this.tabox_pos_start);
			var pos_end = this.calcMarginTop(tabox, this.tabox_pos_end);

			if (show) {
				if (WH.isMobileDomain) {
					$(tabox).show();
				}
				else {
					if (!$(tabox).is(':visible')) $(tabox).css('marginTop', pos_end);

					$(tabox)
						.stop(true)
						.fadeIn({queue: false, duration: this.tabox_duration})
						.animate({ marginTop: pos_start, opacity: 1 }, this.tabox_duration);
				}
			}
			else {
				//HIDE
				if (WH.isMobileDomain) {
					$(tabox).hide();
				}
				else {
					$(tabox)
						.animate({ marginTop: pos_end }, this.tabox_duration)
						.fadeOut({queue: false, duration: this.tabox_duration});
				}
			}
		},

		showAnswerConfirmation: function($li) {
			this.hideAllSocialLoginForms();
			var conf_msg = WH.isMobileDomain ? 'qa_proposed_answer_confirmation_mobile' : 'qa_proposed_answer_confirmation_desktop';
			var html = Mustache.render(unescape($('#qa_answer_confirmation').html()), {
				qa_proposed_answer_confirmation: mw.msg(conf_msg)
			});
			$li.find('.qa_li_container').html(this.escapeHtml(html));
			$li.find('.x_button').click(function() {
				$li.hide();
			});
		},

		//Flag as... follow-up modal
		popFlagAsDetailsModal: function(option, qfa_id, question, answer) {
			mw.loader.using('ext.wikihow.flag_as_details', function() {
				var fad = WH.FlagAsDetails;
				fad.option 		= option;
				fad.qfa_id 		= qfa_id;
				fad.question 	= question;
				fad.answer 		= answer;
				fad.popModal();
			});
		},

		// Social login methods

		showSocialLoginForm: function($li) {
			this.hideAllSocialLoginForms();
			var html = Mustache.render(unescape($('#qa_social_login_form').html()), {
				qa_thanks_for_answer: mw.msg('qa_thanks_for_answer'),
				qa_social_login_form_cta: mw.msg('qa_social_login_form_cta'),
				qa_social_login_disclaimer: mw.msg('qa_social_login_disclaimer')
			});
			$li.find('.qa_li_container').html(this.escapeHtml(html));
			return this.initSocialLoginForm($li);
		},

		showSocialLoginFormConfirmation: function($li, user) {
			var html = Mustache.render(unescape($('#qa_social_login_confirmation').html()), {
				avatarUrl: user.avatarUrl,
				qa_thanks_for_social_login: mw.msg('qa_thanks_for_social_login', user.realName),
				qa_info_after_social_login: mw.msg('qa_info_after_social_login')
			});
			$li.find('.qa_li_container').html(this.escapeHtml(html));
			this.hideAllSocialLoginForms();
			$li.find('.x_button, .qa_social_login_done').click(function() {
				$li.hide();
				window.location.reload();
			});
		},

		hideAllSocialLoginForms: function() {
			$('.qa_social_login_form').closest('li').hide();
		},

		showSocialLoginFormError: function($li) {
			$li.find('.qa_li_container').html(mw.msg('qa_social_login_error'));
		},

		isSocialAuthLoaded: function() {
			return typeof window.WH.social != 'undefined';
		},

		initSocialLoginForm: function($li) {
			var widget = WH.QAWidget;

			$li.find('.x_button').click(function() {
				$li.hide();
			});

			var whLoginDone = function(data) {
				if (data.result == 'signup' && typeof WH.maEvent == 'function') {
					var properties = {
						category: 'account_signup',
						type: data.type,
						prompt_location: 'qa'
					};
					WH.maEvent('account_signup', properties, false);
				}
				widget.showSocialLoginFormConfirmation($li, data.user);
				$.ajax({
					type: 'GET',
					dataType: 'jsonp',
					jsonpCallback: 'wh_jsonp_qa',
					url: 'https://' + window.location.hostname + '/Special:QA',
					data: { a: 'su', sqids: widget.sqids }
				}).done(function() { widget.sqids = []; });
			};

			var whLoginFail = function() {
				widget.showSocialLoginFormError($li);
			};

			return WH.social.setupAutoLoginButtons( {
				fb: '.qa_social_login_form .facebook_button',
				gplus: '.qa_social_login_form .google_button'
			}, whLoginDone, whLoginFail );
		},

		logPatrolEvent: function(action, $li, eventProps) {
			var event = 'qapatrol_action_article'; //always the same event (Alissa request)
			eventProps['action'] = action;
			eventProps['category'] = 'qa_patrol';
			eventProps['original_answer'] = $li.find('.qa_answer').html();
			eventProps['original_question'] = $li.find('.qa_q_txt').html();
			eventProps['patroller_name'] = mw.config.get('wgUserName');
			eventProps['qs_id'] = $li.data('sqid');
			eventProps['qap_id'] = $li.data('qapid');
			eventProps['article_title'] = mw.config.get('wgPageName');
			eventProps['article_id'] = mw.config.get('wgArticleId');
			eventProps['submit_time'] = $li.data('timestamp');
			eventProps['verifier_id'] = $li.data('verifier_id');
			var userName = $li.find(".qa_user_name").text();
			eventProps['submitter_name'] = (userName == mw.msg('qa_generic_username')) ? "Anonymous" : userName;
			if(action == "approve_with_edit") {
				eventProps['edited_answer'] = $li.find('.qa_ca_text').val();
				eventProps['edited_question'] = $li.find('.qa_cq_text').val();
				eventProps['edited_verifier_id'] = $li.find('.qa_edit_form_verifier').find(":selected").val();
				eventProps['edited_submitter_name'] = $li.find('#qa_aq_remove_submitter').is(':checked') ? "" : eventProps['submitter_name'];
			}

			WH.maEvent(event, eventProps, false);
		},

		/**
		 * finishUserLinks()
		 *
		 * Because of our caching needs & isGoodUserPage() checks
		 * we have to assume any not-good-user-pages are unlinked.
		 * This fixes it for the logged-in users
		 */
		finishUserLinks: function() {
			if (mw.config.get('wgUserId') != null) {
				$('.qa_user_link').each(function() {
					var link = document.createElement('a');
					link.innerHTML = $(this).html();
					link.href = $(this).data('href');
					link.target = '_blank';
					$(this).replaceWith(link);
				});
			}
		},

		/**
		 * calcMarginTop()
		 * - calculate the margin-top offset for hover/pop bubble dialog thingies
		 *
		 * @param obj = the bubble
		 * @param added_h = extra height to add
		 */
		calcMarginTop: function(obj, added_h) {
			var mt = $(obj).height()+ added_h;
			mt = mt * -1; //make negative
			mt = mt + 'px'; //make a pixel value
			return mt;
		}

	};

	WH.QAWidget.init();
}($, mw));
