(function( window, document, $) {
	'use strict';

	$('#eq_select_event').select2({ width: '90%' });

	$('#eq_date_start').datepicker({
		changeMonth: true,
		changeYear: true,
		maxDate: new Date(),
		minDate: new Date(2020, 1, 1),
		dateFormat: 'M d, yy',
	}).datepicker('setDate', '-1M');

	$('#eq_date_end').datepicker({
		changeMonth: true,
		changeYear: true,
		maxDate: new Date(),
		minDate: new Date(2020, 1, 1),
		dateFormat: 'M d, yy',
	}).datepicker('setDate', '-1d');

	$('#eq_gb_checkall').click(function() {
		$('#eq_groupby_fieldset input:checkbox').not(this).prop('checked', this.checked);
	});

	$('#eq_groupby_fieldset input:checkbox:not(#eq_gb_checkall)').click(function() {
		$('#eq_gb_checkall').prop('checked', false);
	});

	$('#eq_form').submit(function() {
		if ( ! $('#eq_select_event').val().length ) {
			alert('Please select an event');
			return false;
		}
	});

}(window, document, jQuery));
