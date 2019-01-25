(function($,mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.TopicTaggingAdmin = {
		tool_url: '/Special:TopicTaggingAdmin',

		init: function() {
			this.adjustElements();
			this.addHandlers();
		},

		adjustElements: function() {
			$('.wh_block').prepend($('#tta_add_new_button'));
		},

		addHandlers: function() {
			$('.tta_report').click(function() {
				WH.TopicTaggingAdmin.runReport(this);
			});

			$('.tta_enabled').click(function() {
				WH.TopicTaggingAdmin.toggleEnabled(this);
			});

			$('.tta_edit').click(function() {
				WH.TopicTaggingAdmin.loadForm(this);
			});

			$('#tta_add_new_button').click($.proxy(function() {
				this.showForm();
			},this));
		},

		runReport: function(obj) {
			var job_id = $(obj).closest('.tta_job_row').data('job_id');
			var report_url = this.tool_url+'?action=run_report&job_id='+job_id;
			window.location.href = report_url;
		},

		toggleEnabled: function(obj) {
			var new_enabled_state;

			if ($(obj).hasClass('fa-toggle-on')) {
				$(obj).addClass('fa-toggle-off').removeClass('fa-toggle-on');
				new_enabled_state = 0;
			}
			else {
				$(obj).addClass('fa-toggle-on').removeClass('fa-toggle-off');
				new_enabled_state = 1;
			}

			var job_id = $(obj).closest('.tta_job_row').data('job_id');
			var save_button = $(obj).siblings('.tta_change_enabled');

			$(save_button).show().click($.proxy(function() {
				$(save_button).prop('disabled',true);
				this.changeEnabled(job_id, new_enabled_state);
			},this));
		},

		changeEnabled: function(job_id, enabled) {
			$.getJSON(
				this.tool_url+'?action=change_job_state&job_id='+job_id+'&enabled='+enabled,
				function(data) {
					window.location.reload();
				}
			);
		},

		loadForm: function(obj) {
			var job_id = $(obj).closest('.tta_job_row').data('job_id');

			$.getJSON(
				this.tool_url+'?action=get_job_details&job_id='+job_id,
				$.proxy(function(data) {
					this.showForm(data);
				},this)
			);
		},

		showForm: function(data) {
			var job_id = 0;
			var job_name = '';
			var job_question = '';
			var job_description = '';
			var enabled = 0;
			var new_job = 1;

			if (typeof(data) != 'undefined') {
				new_job = 0;
				job_id = data.id;
				job_name = data.topic.replace(/\"/g,'&quot;');
				job_question = data.question;
				job_description = data.description;
				enabled = data.enabled ? 1 : 0;
			}

			var html = this.escapeHtml(Mustache.render(unescape($('#topic_tagging_admin_edit').html()), {
				job_id: 								job_id,
				enabled: 								enabled,
				job_name_label: 				mw.message('tta_job_name_label').text(),
				job_name: 							job_name,
				job_question_label: 		mw.message('tta_job_question_label').text(),
				job_question: 					job_question,
				job_description_label: 	mw.message('tta_job_description_label').text(),
				job_description: 				job_description,
				articles_prompt: 				mw.message('tta_articles_prompt').text(),
				articles_example: 			mw.message('tta_articles_example').text(),
				job_submit_button: 			mw.message('submit').text(),
				job_done_button: 				mw.message('tta_done_button').text(),
				new: 										new_job
			}));

			$.modal(html, {
				zIndex: 100000007,
				maxWidth: 500,
				minWidth: 500,
				overlayCss: { "background-color": "#000" }
			});

			this.addFormHandlers();
		},

		addFormHandlers: function() {
			$('.fa-times').click(function() {
				$.modal.close();
			});

			$('#tta_edit_submit').click(function() {
				WH.TopicTaggingAdmin.submitForm(this);
			});
		},

		submitForm: function(obj) {
			var form = $(obj).closest('.tta_job_edit');
			this.processing(true);

			if (!this.validateForm(form)) {
				this.processing(false);
				return;
			}

			$.post(
				this.tool_url,
				{
					action: 'save_job',
					job_id: $(form).data('job_id'),
					job_name: $(form).find('#tta_job_name').val(),
					job_question: $(form).find('#tta_job_question').val(),
					job_description: $(form).find('#tta_job_description').val(),
					article_list: $('#tta_article_list').length ? $('#tta_article_list').val() : '',
					enabled: $(form).data('enabled')
				},
				$.proxy(function(result) {
					if (result.success) {
						this.showMessage(result.message);
						this.showCloseButton();
					}
					else {
						this.showError(result.message);
						this.processing(false);
					}

				},this),
				'json'
			);
		},

		validateForm: function(form) {
			var err = '';

			if ($(form).find('#tta_job_name').val().trim() == '') {
				err = mw.message('tta_err_no_topic').text();
			}
			else if ($(form).find('#tta_job_question').val().trim() == '') {
				err = mw.message('tta_err_no_question').text();
			}
			else if ($(form).find('#tta_job_description').val().trim() == '') {
				err = mw.message('tta_err_no_description').text();
			}
			else if ($(form).data('job_id') == 0 && $(form).find('#tta_article_list').val().trim() == '') {
				err = mw.message('tta_err_no_articles').text();
			}

			if (err.length) {
				this.showError(err);
				return false;
			}

			return true;
		},

		escapeHtml: function (htmlString) {
			return $('<textarea/>').html(htmlString).text();
		},

		showMessage: function(msg) {
			$('#tta_message').removeClass('tta_error').html(msg);
		},

		showError: function(err) {
			$('#tta_message').addClass('tta_error').html(err);
		},

		showCloseButton: function() {
			$('#tta_edit_done').show().click(function() {
				$.modal.close();
				window.location.reload();
			});
		},

		processing: function(is_processing) {
			if (is_processing) {
				$('#tta_message').html('');
				$('#tta_edit_submit').fadeOut();
			}
			else {
				$('#tta_edit_submit').fadeIn();
			}
		}
	}

	$(document).ready(function() {
		WH.TopicTaggingAdmin.init();
	});
})(jQuery,mw);
