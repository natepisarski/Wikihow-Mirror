(function($) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.TopicTaggingAdmin = {
		init: function() {
			this.addHandlers();
		},

		addHandlers: function() {
			$('#tta_submit').click($.proxy(function() {
				this.submitForm();
			},this));

			$('#tta_topic').change($.proxy(function() {
				this.reportButtonDisplay();
			},this));

			$('#tta_report').click($.proxy(function() {
				this.runReport();
			},this));
		},

		submitForm: function() {
			this.processing(true);

			if (!this.validateForm()) {
				this.processing(false);
				return;
			}

			$.post(
				'/Special:TopicTaggingAdmin',
				{
					action: 'add_collection',
					topic: $('#tta_topic').val(),
					article_list: $('#tta_article_list').val()
				},
				$.proxy(function(result) {
					if (result.success)
						this.showMessage(result.message);
					else
						this.showError(result.message);

					this.processing(false);
				},this),
				'json'
			);
		},

		validateForm: function() {
			var err = '';

			if (!$('#tta_topic').val() || $('#tta_topic').val() == 0) {
				err = mw.message('tta_err_no_topic').text();
			}
			else if ($('#tta_article_list').val() == '') {
				err = mw.message('tta_err_no_articles').text();
			}

			if (err.length) {
				this.showError(err);
				return false;
			}

			return true;
		},

		runReport: function() {
			var report_url = '/Special:TopicTaggingAdmin?action=run_report&topic_id='+$('#tta_topic').val();
			window.location.href = report_url;
		},

		reportButtonDisplay: function() {
			if ($('#tta_topic').val() > 0)
				$('#tta_report').show();
			else
				$('#tta_report').hide();
		},

		showMessage: function(msg) {
			$('#tta_message').removeClass('tta_error').html(msg);
		},

		showError: function(err) {
			$('#tta_message').addClass('tta_error').html(err);
		},

		processing: function(is_processing) {
			if (is_processing) {
				$('#tta_message').html('');
				$('#tta_submit').fadeOut();
			}
			else {
				$('#tta_submit').fadeIn();
			}
		}
	}

	$(document).ready(function() {
		WH.TopicTaggingAdmin.init();
	});
})(jQuery);