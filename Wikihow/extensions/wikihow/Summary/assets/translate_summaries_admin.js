(function($,mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.TranslateSummariesAdmin = {
		tool_url: '/Special:TranslateSummariesAdmin',
		date_format: 'mm/dd/yy',
		initial_date_range: 14,

		init: function() {
			this.addHandlers();
			this.initDateRange();
		},

		addHandlers: function() {
			$('#upload_file').change($.proxy(function() {
				this.import();
				return false;
			},this));

			$('#run_big_report').click(function() {
				$('#tsa_big_report').submit();
				return false;
			});

			$('#delete_language_select').change(function() {
				if ($(this).val() != '') WH.TranslateSummariesAdmin.showDeleteForm($(this).val());
			});

			$('#tsa_delete_summaries').submit($.proxy(function() {
				this.hideDeleteForm();
			},this));

			var from = $( "#tsa_range_from" )
				.datepicker({
					changeMonth: true,
					numberOfMonths: 1,
					maxDate: new Date()
				})
				.on( "change", $.proxy(function() {
					to.datepicker( "option", "minDate", this.getDate( this ) );
				},this));

			var to = $( "#tsa_range_to" )
				.datepicker({
					changeMonth: true,
					numberOfMonths: 1,
					maxDate: new Date()
				})
				.on( "change", $.proxy(function() {
					from.datepicker( "option", "maxDate", this.getDate( this ) );
				},this));
		},

		import: function() {
			var filename = $('#upload_file').val();
			if (!filename) {
				console.log('No file selected!');
			} else {
				$('#tsa_upload').submit();
			}
		},

		initDateRange: function() {
			var d = new Date();
			$('#tsa_range_to').val($.datepicker.formatDate(this.date_format, d));
			d.setDate(d.getDate() - this.initial_date_range);
			$('#tsa_range_from').val($.datepicker.formatDate(this.date_format, d));
		},

		getDate: function( element ) {
			var date;
			try {
				date = $.datepicker.parseDate(this.date_format, element.value );
			} catch( error ) {
				date = null;
			}

			return date;
		},

		showDeleteForm: function(lang_code) {
			$('#tsa_delete_summaries textarea').val('');
			$('#tsa_delete_summaries').slideDown();
			$('#delete_language').val(lang_code);
		},

		hideDeleteForm: function() {
			$('#tsa_delete_summaries').hide();
			$('#delete_language_select').val('');
		}
	}

	$(document).ready(function() {
		WH.TranslateSummariesAdmin.init();
	});

})(jQuery,mw);