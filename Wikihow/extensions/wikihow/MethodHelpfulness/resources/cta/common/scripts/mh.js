(function () {
	'use strict';
	window.WH = WH || {};
	window.WH.MethodHelpfulness = function () {};

	window.WH.MethodHelpfulness.prototype = {
		/**
		 * Delegates a click event binding to the submit button child element under
		 * the given element.
		 */
		initialize: function (elem) {
			elem.delegate('.mh-submit:not(.mh-submit-custom)', 'click', $.proxy(this.prepareSubmit, this));
		},

		/**
		 * CTA-specific subclasses should implement this.
		 */
		prepareSubmit: function (e) {
			return false;
		},

		/**
		 * Should be invoked when user hits submit.
		 */
		submit: function (data) {
			data.action = 'submit';

			$.post(this.toolURL, data, function () { return; }, 'json')
				.done($.proxy(this.submitDone, this))
				.fail($.proxy(this.submitFail, this))
				.always($.proxy(this.submitAlways, this));
		},

		/**
		 * Always called on submit.
		 * CTA-specific subclasses may override this if necessary.
		 */
		submitAlways: function (result) {
			return;
		},

		/**
		 * On AJAX success.
		 */
		submitDone: function (result) {
			if (!result || result.error) {
				this.submitError(result);
			} else {
				this.submitSuccess(result);
			}
		},

		/**
		 * When submission returns result with error code.
		 * CTA-specific subclasses may override this for more control over errors.
		 */
		submitError: function (result) {
			if (!result || !result.error) {
				alert('An unknown error has occurred.');
			} else {
				alert(result.error);
			}
		},

		/**
		 * When AJAX request for submission fails.
		 */
		submitFail: function (result, t, e) {
			alert('A server error has occurred.');
		},

		/**
		 * When submission returns successful result (without error code).
		 * CTA-specific subclasses should override this.
		 */
		submitSuccess: function (result) {
			return;
		},

		toolURL: '/Special:MethodHelpfulness'
	};
}());
