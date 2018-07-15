(function($) {
	'use strict';

	window.WH = window.WH || {};
	var sheetInv = window.WH.sheetInv;	// "Package"

	/**
	 * Extend sheetInv.BaseClass
	 */
	sheetInv.WVInvoicing = function() {
		sheetInv.BaseClass.call(this, '/Special:WikiVisualInvoicing');
	};
	sheetInv.WVInvoicing.prototype = Object.create(sheetInv.BaseClass.prototype);
	sheetInv.WVInvoicing.prototype.constructor = sheetInv.WVInvoicing;

	/**
	 * Get settings for the email
	 * @return Object or false on failure
	 */
	sheetInv.WVInvoicing.prototype.getSettings = function()
	{
		var $settings = $('#sheetInv_settings');
		var subject_w_loan = $settings.find('input[name="subject_w_loan"]').val();
		var subject_wo_loan = $settings.find('input[name="subject_wo_loan"]').val();
		var report_recipients = $settings.find('input[name="report_recipients"]').val();

		if (!subject_w_loan || !subject_wo_loan || !report_recipients) {
			return false;
		}

		return {
			subject_w_loan: subject_w_loan,
			subject_wo_loan: subject_wo_loan,
			report_recipients: report_recipients
		};
	};

	$(document).ready(function()
	{
		(new sheetInv.WVInvoicing()).attachHandlers();
	});

}(jQuery));
