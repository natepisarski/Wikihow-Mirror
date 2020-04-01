(function(){
	var url = '/' + wgPageName;

	function getData() {
		var data = {
			a: $('#ct_a').text(), 
			data: $('#ct_data').val(),
			intonly: 0,
			alts: 0
		};
		
		if ($('#ct_a').text() != 'ids') {
			data.intonly = $('#ct_introonly').is(':checked');
			data.alts = $('#ct_slow').is(':checked');
		}
		return data;
	}

	$(document).on('click','#ct_button', function(e) {
		var data = getData();
		data.format = 'csv';
		$.download(url, data);
	});

	$(document).on('click','#ct_button_html', function(e) {
		var data = getData();
		data.format = 'html';
		$('#ct_html').html('loading...');
		$.post(url, data, function(result) {
			$('#ct_html').html(result);
		});
	});
}(jQuery));