(function($, mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.QAAdmin = {
		MAX_IDS_ALLOWED: 2000,
		submitUrl:  '/' + mw.config.get('wgPageName'),
		init: function() {
			this.initListeners();
		},

		initListeners: function () {

			$(document).on('keyup', '.qa_textarea', function(e) {
				var newLines = $(this).val().split("\n").length;
				var maxIds = WH.QAAdmin.MAX_IDS_ALLOWED;
				if (newLines > maxIds) {
					alert('A maximum of ' + maxIds + ' can be submitted.');
					$(this).val('');
				}
			});

			$(document).on('click', '#qa_sqids_approve_submit', $.proxy(function(e) {
				e.preventDefault();
				$('.qa_success').hide();
				var sqids = $('#qa_approve_sqids').val().trim();
				if (sqids.length) {
					sqids = sqids.split(/\n/);
					$.post(
						this.submitUrl,
						{a: 'sqids_approve', sqids: sqids},
						function() {
							$('.qa_approved_success').html(mw.msg('qa_approved_success')).show();
						}
					);
				}
			}, this));

			$(document).on('click', '#qa_sqids_ignore_submit', $.proxy(function(e) {
				e.preventDefault();
				$('.qa_success').hide();
				var sqids = $('#qa_ignore_sqids').val().trim();
				if (sqids.length) {
					sqids = sqids.split(/\n/);
					$.post(
						this.submitUrl,
						{a: 'sqids_ignore', sqids: sqids},
						function() {
							$('.qa_ignored_success').html(mw.msg('qa_ignored_success')).show();
						}
					);
				}
			}, this));

			$(document).on('click', '#qa_update_csv_submit', $.proxy(function(e) {
				e.preventDefault();
				$('.qa_success').hide();
				var csv = $('#qa_update_csv').val().trim();
				if (csv.length) {
					$.post(
						this.submitUrl,
						{a: 'sqids_update_text', csv: csv},
						function() {
							$('.qa_update_success').html(mw.msg('qa_update_success')).show();
						}
					);
				}
			}, this));


		}
	};

	WH.QAAdmin.init();
}($, mw));
