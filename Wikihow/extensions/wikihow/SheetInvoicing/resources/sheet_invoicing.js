(function($) {
	'use strict';

	window.WH = window.WH || {};
	var sheetInv = window.WH.sheetInv = {}; // "Package"

	/**
	 * Base "class" constructor
	 */
	sheetInv.BaseClass = function(apiUrl) {
		this.apiUrl = apiUrl;
		this.container = '#sheet_invoicing';
	};

	/**
	 * Get settings for the email (subject, etc). To be implemented by subclasses.
	 * @return Object or false on failure
	 */
	sheetInv.BaseClass.prototype.getSettings = null;

	/**
	 * Validate the "Settings" section and send out the invoices by email
	 */
	sheetInv.BaseClass.prototype.emailInvoices = function(btn)
	{
		var settings = this.getSettings();

		if (settings === false) {
			alert('Please complete the "Settings" section.');
			return;
		}

		if (!confirm('Are you sure?')) {
			return;
		}

		$(btn).attr('disabled', true);

		var $container = $(this.container);

		$.post(this.apiUrl, settings)
		.done(function(data) {
			$container.html(data);
		})
		.fail(function(jqXHR, textStatus, errorThrown) {
			$container.html('<b>An error occurred: </b>' + errorThrown);
		})
		.always(function() {
			window.scrollTo(0, 0);
		});
	};

	/**
	 * Show an email preview in a popup when a table row is clicked
	 */
	sheetInv.BaseClass.prototype.showPreview = function(row)
	{
		var name = $(row).data('name');
		var html = $('#sheet_invoicing').find('.preview_div[data-name="' + name + '"]').html();
		var title = 'Email Preview: ' + name;
		var options = 'width=700,height=700';
		var popup = window.open('', title, options);
		popup.document.write(html);
		popup.document.title = title;
	};

	sheetInv.BaseClass.prototype.attachHandlers = function()
	{
		var self = this;

		$('#sheetInv_send_btn').on('click', function() {
			self.emailInvoices(this);
		});

		$('.preview_row').on('click', function() {
			self.showPreview(this);
		});
	};

}(jQuery));
