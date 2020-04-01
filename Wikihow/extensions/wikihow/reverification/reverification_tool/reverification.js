
(function($, mw)  {
	'use strict';
	window.WH = window.WH || {};
	window.WH.Reverfication = {
		ACTION_NEXT: 'next',
		ACTION_REVERIFY: 'reverify',
		ACTION_SKIP: 'skip',
		ACTION_QUICK_FEEDBACK: 'short_feedback',
		ACTION_EXTENSIVE_FEEDBACK: 'long_feedback',
		endpoint: '/Special:Reverification',
		responseData: {a: '', viewUsername: '', overrideUsername: '', overrideVerifierUsername: '', rever_id: 0, rid_reset: 0},
		$root: $('#rv_tools_container'),

		init: function() {
			this.initListeners();
			this.getNext();
		},

		initListeners: function() {
			var $container = $('#rv_buttons');
			$container.on('click', '#rv_btn_verified', $.proxy(function(e) {
				e.preventDefault();
				this.onReverify();
			}, this));


			$container.on('click', '#rv_btn_quick_feedback', $.proxy(function(e) {
				e.preventDefault();
				this.onQuickFeedback();
			}, this));

			$container.on('click', '#rv_btn_send_quick_feedback', $.proxy(function(e) {
				e.preventDefault();
				this.onSendQuickFeedback();
			}, this));

			$container.on('click', '#rv_btn_cancel_quick_feedback', $.proxy(function(e) {
				e.preventDefault();
				this.onCancelQuickFeedback();
			}, this));


			$container.on('click', '#rv_btn_extensive_feedback', $.proxy(function(e) {
				e.preventDefault();
				this.onExtensiveFeedback();
			}, this));

			$container.on('click', '#rv_btn_skip', $.proxy(function(e) {
				e.preventDefault();
				this.onSkip();
			}, this));

		},

		getNext: function() {
			this.setLoading(true);
			$.post(this.endpoint,
				this.getPostVars(this.ACTION_NEXT),
				function (result) {
					WH.Reverfication.loadResult(result);
				},
				'json'
			);
		},

		loadResult: function(result) {
			this.responseData = result;
			if (result['rever_id']) {
				this.setTitle(result['title']);
				$('#rv_article').html(result['html']);
				this.setLoading(false);
			} else if (result['error_msg']) {
				this.setEndOfQueue();
				this.setTitle(result['error_msg']);

			} else if (result['status_msg']) {
				this.setEndOfQueue();
				this.setTitle(mw.msg('rv_status', result['status_msg']));
			} else {
				this.setEndOfQueue();
				this.setTitle(mw.msg('rv_status_eoq'));
			}

		},

		setTitle: function(title, url) {
			if (url) {
				$('.firstHeading').html($('<a>').attr('href', url).attr('target', '_blank').html(title));
			} else {
				$('.firstHeading').html(title);
			}

		},

		setEndOfQueue: function() {
			this.$root.addClass('rv_eoq');
			this.setTitle();
		},

		getPostVars: function(action) {
			return {
				a: action,
				viewUsername: mw.util.getParamValue('viewUsername'),
				overrideUsername: mw.util.getParamValue('overrideUsername'),
				overrideVerifierUsername: mw.util.getParamValue('overrideVerifierUsername'),
				rever_id: this.responseData['rever_id'],
				rid_new: this.responseData['rid_new'],
				rid_reset: mw.util.getParamValue('rid_reset')
			}
		},

		onReverify: function() {
			this.setLoading(true);

			var vars = this.getPostVars(this.ACTION_REVERIFY);

			$.post(this.endpoint,
				vars,
				function (result) {
					WH.Reverfication.loadResult(result);
				},
				'json'
			);
		},

		onSkip: function() {
			this.setLoading(true);

			var vars = this.getPostVars(this.ACTION_SKIP);
			$.post(this.endpoint,
				vars,
				function (result) {
					WH.Reverfication.loadResult(result);
				},
				'json'
			);
		},

		onQuickFeedback: function() {
			this.$root.addClass('rv_quick_feedback');
			$('#rv_quick_feedback').val("");
		},

		onSendQuickFeedback: function() {
			var feedback = $('#rv_quick_feedback').val();

			if (feedback.length === 0) {
				return;
			}

			this.setLoading(true);

			var vars = this.getPostVars(this.ACTION_QUICK_FEEDBACK);
			vars['rv_quick_feedback'] = feedback;

			$.post(this.endpoint,
				vars,
				function (result) {
					WH.Reverfication.$root.removeClass('rv_quick_feedback');
					WH.Reverfication.loadResult(result);
				},
				'json'
			);
		},

		onCancelQuickFeedback: function() {
			this.$root.removeClass('rv_quick_feedback');
		},

		onExtensiveFeedback: function() {
			this.setLoading(true);

			var vars = this.getPostVars(this.ACTION_EXTENSIVE_FEEDBACK);

			$.post(this.endpoint,
				vars,
				function (result) {
					WH.Reverfication.loadResult(result);
				},
				'json'
			);
		},

		setLoading: function(isLoading) {
			if (isLoading) {
				this.$root.addClass('rv_loading');
				$('.firstHeading').html(mw.msg('rv_status_loading'));
				$('#rv_article').slideUp();
				$('#rv_tools').slideUp();
			} else {
				this.$root.removeClass('rv_loading');
				$('#rv_article').slideDown();
				$('#rv_tools').slideDown();
			}
		}

	};

	$(document).ready(WH.Reverfication.init());

}(jQuery, mw));