(function($, mw)  {
	'use strict';
	window.WH = window.WH || {};
	window.WH.QAPatrolStats = {
		toolUrl: "/Special:QAPatrol",
		date_format: 'mm/dd/yy',
		expert_mode: false,
		top_answerer_mode: false,
		user_name: '',

		init: function() {
			this.expert_mode = $('#expert_mode').length > 0;
			this.top_answerer_mode = $('#top_answerer_mode').length > 0;
			this.user_name = $('#user_name').html();

			this.initDateRange();
			this.initListeners();
		},

		initDateRange: function() {
			var d = new Date();
			$('#qaps_range_to').val($.datepicker.formatDate(this.date_format, d));
			d.setDate(d.getDate() - 14);
			$('#qaps_range_from').val($.datepicker.formatDate(this.date_format, d));

			var from = $( "#qaps_range_from" )
				.datepicker({
					changeMonth: true,
					numberOfMonths: 1,
					maxDate: new Date()
				})
				.on( "change", function() {
					to.datepicker( "option", "minDate", WH.QAPatrolStats.getDate( this ) );
				});

			var to = $( "#qaps_range_to" )
				.datepicker({
					changeMonth: true,
					numberOfMonths: 1,
					maxDate: new Date()
				})
				.on( "change", function() {
					from.datepicker( "option", "maxDate", WH.QAPatrolStats.getDate( this ) );
				});

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

		initListeners: function() {
			$('#qap_export').click($.proxy(function() {
				this.exportCSV();
				return;
			}, this));
		},

		exportCSV: function() {
			var url = this.toolUrl + '?action=export'+
				'&from='+encodeURIComponent($('#qaps_range_from').val())+
				'&to='+encodeURIComponent($('#qaps_range_to').val());

			if (this.expert_mode) url += '&expert=true';
			if (this.top_answerer_mode) url += '&ta=true';
			if (this.user_name) url += '&user='+this.user_name;

			window.location.href = url;
		}

	};

	$(document).ready(WH.QAPatrolStats.init());

}(jQuery, mw));