<style>
td,th {
	text-align: left;
	padding:5px;/
}

.ct_cat {
	width: 400px;
	height: 17px;
}

.ct_urls {
	width: 600px;
	height: 300px;
}

#ct_a {
	display: none;
}

.ct_row {
	margin: 5px 0 5px 0;
}
</style>
<script type='text/javascript'>
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
</script>
<div id='ct_a'><?=$ct_a?></div>

<? if ($ct_a == 'cats') { ?>
<label for="ct_cat"><b>Enter Category URL</b> </label><input type="text" class="ct_cat" name="ct_cat" id="ct_data"/>
<? } elseif ($ct_a == 'ids') { ?>
<label for="ct_urls"><b>Enter Article  IDs</b> </label>
<? } else { ?>
<label for="ct_urls"><b>Enter Article  URLs</b> </label>
<? } ?>
<div>
<textarea class="ct_urls" name="ct_urls" id="ct_data"/></textarea>
</div>

<? if ($ct_a != "ids") { ?>
<div class='ct_row'>
<label for="ct_slow"><b>Include Slower Data (alt methods, images and article size)</b> </label><input type="checkbox" name="ct_slow" id="ct_slow" />
</div>
<div class='ct_row'>
<label for="ct_introonly"><b>Intro Image only</b> </label><input type="checkbox" name="ct_introonly" id="ct_introonly" />
</div>
<? } ?>
<div class='ct_row'>
<input type='button' id='ct_button' value='Get File'></input>
<input type='button' id='ct_button_html' value='Get Html'></input>
</div>
<div id='ct_html'></div>
