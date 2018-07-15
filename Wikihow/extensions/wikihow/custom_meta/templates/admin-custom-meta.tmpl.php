<a href="/<?= $switchPage ?>">switch to editing <?= $switchName ?></a> &raquo;<br>
<a href="/<?= $switchPage2 ?>">switch to editing <?= $switchName2 ?></a> &raquo;
<form id='admin-upload-form' name='adminUploadForm' enctype='multipart/form-data' method='post' action='/Special:<?= $action ?>' onsubmit="return AIM.submit(this, { onStart: function () { $('#admin-result').html('sent!'); }, onComplete: function (data) { console.log('d',data); $('#admin-result').html(''); onFormSubmitted(); } });">
<input type="hidden" name="action" value="save-list" />
<br/>
<style>
	.sm { font-variant:small-caps; letter-spacing:2px; margin-right: 25px; background-color: #ffffe5 }
	.bx { padding: 5px 10px 5px 10px; margin-bottom: 15px; border: 1px solid #dddddd; border-radius: 10px 10px 10px 10px; background-color: #fff }
	.btn { padding:5px; font-size:14px }
	td { vertical-align: top }
	tr:nth-child(even) {background: #EEE}
	th { background: #e5eedd }
</style>
<div class=bx>
	<span class=sm>Download</span>
	<button id="admin-get" class="btn">retrieve current list</button><br/>
</div>
<div class=bx>
	<span class=sm>Upload</span>
	<input type="file" id="adminFile" name="adminFile" class="btn"><br/>
</div>
<br/>
<div class=bx>
	<span class=sm>Processing Results</span><br/>
	<br/>
	<div id="admin-result">
	</div>
</div>
</form>
<br/>
<div class=bx id="recent-summary">
	<span class=sm>Recent Activity</span><br/><br/>
	<tt>
		<?= $recent ?>
	</tt>
</div>

<script>
(function($) {
	$(document).ready(function() {

		$('#admin-get').click(function () {
			$('#admin-result').html('retrieving list ...');
			var url = '/Special:<?= $action ?>';
			var form = 'action=retrieve-list';
			$.download(url, form);
			return false;
		});

		$('#adminFile').change(function () {
			var filename = $('#adminFile').val();
			if (!filename) {
				alert('No file selected!');
			} else {
				$('#admin-result').html('sending list ...');
				$('#admin-upload-form').submit();
			}
			return false;
		});

	});
})(jQuery);

function onFormSubmitted(data) {
	$('#admin-result').html('saved! reload this page to see status, or to upload again.');
	console.log('e',data);
}
</script>
