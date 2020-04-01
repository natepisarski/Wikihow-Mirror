(function($,mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.TranslateSummariesTool = {
		tool_url: '/Special:TranslateSummaries',
		page_id_intl: '',

		init: function() {
			this.addHandlers();

			var forced_page_id = this.forcedIntlPageId();
			this.getNext(forced_page_id);
		},

		addHandlers: function() {
			$(document)
				.on('click', '#tst_skip', $.proxy(function() {
					this.skip();
					return false;
				},this))
				.on('click', '#tst_publish', $.proxy(function() {
					this.publish();
					return false;
				},this))
				.on('focus', 'textarea', function() {
					$(this).removeClass('error');
					$('#tst_error_message').hide();
				})
				.on('click', '#tst_next', $.proxy(function() {
					this.getNext();
					return false;
				},this));
		},

		forcedIntlPageId: function() {
			var aid = parseInt(mw.util.getParamValue('articleid'));
			if (aid) history.pushState(null, null, window.location.href.split("?")[0]);
			return aid ? aid : '';
		},

		getNext: function(forced_page_id) {
			$.post(
				this.tool_url,
				{
					action: 'get_next_summary',
					forced_page_id: forced_page_id
				},
				$.proxy(function(result) {
					this.page_id_intl = result.page_id_intl;
					this.displayHtml(result.html);
				},this),
				'json'
			);
		},

		displayHtml: function(html) {
			$('html, body').animate({scrollTop: 0}, 300);
			$('#translate_summaries_main').hide().html(html).fadeIn();
		},

		skip: function() {
			$.post(
				this.tool_url,
				{
					action: 'skip',
					page_id_intl: this.page_id_intl
				},
				$.proxy(function() {
					this.getNext();
				},this),
				'json'
			);
		},

		validate: function() {
			//reset to revalidate
			$('#tst_error_message').hide();
			$('#translate_summaries_tool').removeClass('error');

			var bad_inputs = [];

			if ($('#content_intl').val().trim() == '') bad_inputs.push('content_intl');
			if ($('#sentence_en').html() != '' && $('#sentence_intl').val().trim() == '')
				bad_inputs.push('sentence_intl');

			if (bad_inputs.length) {
				$('#tst_error_message').slideDown();
				bad_inputs.forEach(function(bad_input) {
					$('#'+bad_input).addClass('error');
				});
				return false;
			}

			return true;
		},

		publish: function() {
			if (!this.validate()) return;

			$.post(
				this.tool_url,
				{
					action: 'save',
					page_id_intl: this.page_id_intl,
					content: $('#content_intl').val().trim(),
					last_sentence: $('#sentence_intl').val().trim()
				},
				$.proxy(function(result) {
					result ? this.displayHtml(result.html) : this.getNext();
				},this),
				'json'
			);
		}
	}

	$(document).ready(function() {
		WH.TranslateSummariesTool.init();
	});

})(jQuery,mw);