(function($,mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.BulkTopicTagging = {

		tool_url: '/Special:BulkTopicTagging',

		init: function() {
			this.addHandlers();
			$('#btt_reasons').chosen({no_results_text: mw.message('btt_no_tag').text()});
		},

		addHandlers: function() {
			$('#btt_save').click($.proxy(function() {
				this.submitForm();
			},this));

			$(document).on('click', '.create_tag', $.proxy(function() {
				this.createTag();
				return false;
			},this));

			$(document).on('click', '#btt_export', $.proxy(function() {
				this.exportResults();
			},this));
		},

		submitForm: function() {
			this.toggleProcessing();

			var tag_ids = this.getSelectedTagIDs();
			var tag_action = $('input[name=btt_action]:checked').val();
			var article_list = $('#btt_article_ids').val().trim();

			if (!this.validForm(tag_ids, tag_action, article_list)) {
				this.toggleProcessing();
				return;
			}

			$.post(this.tool_url, {
				action: 'update_tags',
				tag_action: tag_action,
				tag_ids: tag_ids.toString(),
				article_list: article_list
			},'json')
			.done($.proxy(function(data) {
				this.showResultMessage(data.results);
				this.toggleProcessing();
			},this));
		},

		getSelectedTagIDs: function() {
			var tags = [];
			$.each($('#btt_reasons option:selected'), function(i, val) {
				val = $(val).attr('id');
				tags.push(unescape(val));
			});

			return tags;
		},

		validForm: function(tag_ids, tag_action, article_list) {
			var err = '';

			if (!tag_ids.length) {
				err = mw.message('btt_err_no_tag').text();
			}
			else if (tag_action == '') {
				err = mw.message('btt_err_no_action').text();
			}
			else if (article_list == '') {
				err = mw.message('btt_err_no_articles').text();
			}

			if (err.length) {
				this.showError(err);
				return false;
			}

			return true;
		},

		showError: function(msg) {
			$('#btt_results').addClass('btt_error').html(msg);
		},

		showResultMessage: function(results) {
			$('#btt_results').removeClass('btt_error').html(results);
		},

		createTag: function() {
			var tag = $('.no-results span').text();
			if (!this.validTag(tag)) return;

			$.post('/Special:SensitiveArticleAdmin', {
				action: 'upsert',
				id: 0,
				name: tag,
				enabled: true,
				from_btt: 1
			},'json')
			.done(function(data) {
				$('#btt_reasons').append($("<option></option>").attr("id", data.tag_id).attr("selected", "selected").text(tag));
				$('#btt_reasons').trigger('liszt:updated');
			});
		},

		validTag: function(tag) {
			if (tag.length < 2 || tag.length > 200) {
				alert('Tag names must be between 2 and 200 characters in length');
				return false;
			}

			if (tag.match(/[\?\\,\"\'\/\-]/) != null) {
				alert('Tag names cannot contain any of the following characters: \' - ? \\ / " , !');
				return false;
			}

			return true;
		},

		toggleProcessing: function() {
			$('#bulk_topic_tagging').toggleClass('processing');
		},

		exportResults: function() {
			var report_url = this.tool_url+'?action=export_results';
			window.location.href = report_url;
		}
	}

	$(document).ready(function() {
		WH.BulkTopicTagging.init();
	});

})(jQuery,mw);