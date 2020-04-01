(function () {
	"use strict";
	window.WH = window.WH || {};
	WH.exporter = {

		dataRanges: {},

		initialize: function (dateRanges) {
			this.dateRanges = dateRanges;
			$('input[name="export[type]"]').change(typeChange).filter(':checked').change();
			$('.state').change(stateChange).change();
		}
	};

	function typeChange (event) {
		var type = $(event.currentTarget).val();
		$('#form-container').removeClass().addClass("type-" + type);

		if (type === 'by-state') {
			$('.state.past-tense').attr('disabled', true).hide();
			$('.state.present-tense').removeAttr('disabled').show();
		} else {
			$('.state.present-tense').attr('disabled', true).hide();
			$('.state.past-tense').removeAttr('disabled').show();
		}
	}

	function stateChange (event) {
		var chosenData = _.findWhere(WH.exporter.dateRanges, {key: $(event.currentTarget).val()});

		if ($('#datepicker').is(':visible')) {
			$("#picker").daterangepicker({
				locale: {format: 'YYYY-MM-DD'},
				minDate: chosenData.start,
				maxDate: chosenData.end,
				autoApply: true,
				timePicker24Hour: true,
				opens: "center",
				startDate: chosenData.start,
				endDate: chosenData.end
			});
		}
	}

}());
