(function($, mw)  {
	'use strict';
	window.WH = window.WH || {};
	window.WH.AdminUserReview = {
		toolUrl: "/Special:AdminUserReview",

		init: function() {
			this.initDateRange();
			this.initListeners();
		},

		initDateRange: function() {
			var d = new Date();
			$('#aur_range_to').val($.datepicker.formatDate('mm/dd/yy', d));
			d.setDate(d.getDate() - 14);
			$('#aur_range_from').val($.datepicker.formatDate('mm/dd/yy', d));

			$('.aur_range_input').datepicker({maxDate: new Date()});
		},

		initListeners: function() {
			$(document).on("click", "#aur_change_dates", $.proxy(function(e) {
				e.preventDefault();
				this.getNewDateData();
			}, this));
		},

		getNewDateData: function() {
			$("#aur_table").hide();
			$.ajax({
				url: this.toolUrl,
				data: {action: 'newDates', from: $("#aur_range_from").val(), to: $("#aur_range_to").val()},
				dataType: 'json',
			}).done(function (response) {
				$("#aur_table").replaceWith(response.reviewertable);
				$("#aur_table").show();
			});
		}

	};

	$(document).ready(WH.AdminUserReview.init());

}(jQuery, mw));