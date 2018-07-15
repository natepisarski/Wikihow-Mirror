(function($) {
	'use strict';

	window.WH = window.WH || {};
	var sheetInv = window.WH.sheetInv;	// "Package"

	/**
	 * Extend sheetInv.BaseClass
	 */
	sheetInv.ExpertInvoicing = function() {
		sheetInv.BaseClass.call(this, '/Special:ExpertInvoicing');
	};
	sheetInv.ExpertInvoicing.prototype = Object.create(sheetInv.BaseClass.prototype);
	sheetInv.ExpertInvoicing.prototype.constructor = sheetInv.ExpertInvoicing;

	/**
	 * Get settings for the email
	 * @return Object or false on failure
	 */
	sheetInv.ExpertInvoicing.prototype.getSettings = function()
	{
		var $settings = $('#sheetInv_settings');
		var email_subject = $settings.find('input[name="email_subject"]').val();
		var email_recipients = $settings.find('input[name="email_recipients"]').val();

		if (!email_subject || !email_recipients) {
			return false;
		}

		return {
			email_subject: email_subject,
			email_recipients: email_recipients
		};
	};

	$(document).ready(function()
	{
		(new sheetInv.ExpertInvoicing()).attachHandlers();
	});

}(jQuery));
