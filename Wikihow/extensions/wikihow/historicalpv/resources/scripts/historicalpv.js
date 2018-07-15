$(document).ready(function() {
	var dateformat = 'mm/dd/yy';
	function getDate(e) {
		var d;
		try {
			d = $.datepicker.parseDate(dateformat, e.value);
		} catch (error) {
			d = null;
		}

		return d;
	}

	var ds = $('#date_start').datepicker({
		changeMonth: true,
		changeYear: true,
		maxDate: new Date(),
		minDate: new Date(2012, 7, 31)
	}).on('change', function() {
		de.datepicker('option', 'minDate', getDate(this));
	});

	var de = $('#date_end').datepicker({
		changeMonth: true,
		changeYear: true,
		maxDate: new Date(),
		minDate: new Date(2012, 7, 31)
	}).on('change', function() {
		ds.datepicker('option', 'maxDate', getDate(this));
	});

	$('select[name=col]').select2({ width: '40%' });
	$('select[name=date_type]').select2({ width: '30%' });

	$('#col').val('ti_30day_views').trigger('change');
	$('#date_type').on('change', function() {
		var v = $(this).val();
		if (v == 'specific') {
			$('#date_range').hide();
			$('#date_list').show();
		} else {
			$('#date_range').show();
			$('#date_list').hide();
		}
	});

	$('#pvform').submit(function(e) {
		if ($('#date_type').val() === 'specific') {
			var l = $('#dates').val().split('\n');
			var sanitized_dates = [];
			$.each(l, function(idx, val) {
				if (val !== '') {
					var p = moment.utc(val);
					if (p.isValid()) {
						sanitized_dates.push(p.toString());
					}
				}
			});
			$('#dates').val(sanitized_dates.join('\n'));
		}

		if (($('#date_type').val() === 'specific' && $('#dates') === '') ||
				($('#date_type').val() !== 'specific' && ($('#date_start').val() === '' || $('#date_end').val() === '')) ||
				($('#email').val() === '') ||
				($('textarea[name=urls]').val() === '' && $('#upload_file').val() === '')) {

			e.preventDefault();
			$('#error_message').text('Invalid inputs. Ensure that you selected a file or URLs, you provided an email, and you set your dates correctly.');
			$('#error').show();
			return false;
		}
	});

	$('a.close').on('click', function() {
		$('div.alert').hide();
	});
});
