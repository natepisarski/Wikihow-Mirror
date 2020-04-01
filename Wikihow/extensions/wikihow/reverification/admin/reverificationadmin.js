(function($, mw)  {
	'use strict';
	window.WH = window.WH || {};
	window.WH.ReverficationAdmin = {
		endpoint: mw.util.getUrl(),
		ACTION_EXPORT_RANGE: 'export_range',
		ACTION_EXPORT_ALL: 'export_all',

		init: function() {
			this.initListeners();
			this.initDateRange();
		},

		initDateRange: function() {
			var d = new Date();
			$('#rva_range_to').val($.datepicker.formatDate('mm/dd/yy', d));
			d.setDate(d.getDate() - 7);
			$('#rva_range_from').val($.datepicker.formatDate('mm/dd/yy', d));

			$('.rva_range_input').datepicker({maxDate: new Date()});
		},

		initListeners: function() {
			var $container = $('#rv_buttons');
			$container.on('click', '#rva_btn_download_range', $.proxy(function(e) {
				e.preventDefault();
				this.onDownloadRange();

			}, this));

			$container.on('click', '#rva_btn_download_all', $.proxy(function(e) {
				e.preventDefault();
				this.download(this.ACTION_EXPORT_ALL);
			}, this));
		},

		onDownloadRange: function() {
			var range = {
				from: $('#rva_range_from').val(),
				to: $('#rva_range_to').val(),
			};

			if (range.from && range.to) {
				this.download(this.ACTION_EXPORT_RANGE, range);
			}
		},

		download: function(type)  {
			var data = $.extend({a: type}, arguments[1] || {});
			$.download(this.endpoint, data);
		}

	};

	$(document).ready(WH.ReverficationAdmin.init());

}(jQuery, mw));