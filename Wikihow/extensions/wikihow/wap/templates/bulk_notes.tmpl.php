<?=$css?>
<?=$js?>
<?=$nav?>
<? if ($add) { ?>
	<div style="margin-bottom: 10px">
		<button id="toggleNoteType" style="padding:5px;" value="toggleCSV" class="button secondary">Switch to CSV input</button>
	</div>

	<div id='fishnotes-normal'>
		<h4>URLs</h4>
		<textarea class="urls input_med" rows="500" name="urls" id="urls">
		</textarea>

		<h4>Notes to apply <span style="font-weight:normal;">(the same notes will be applied to all entered articles):</span></h4>
		<textarea class="input_med" style="width:400px;height:144px;font-size:14px;" name="notes" id="notes">
		</textarea>
		
		<div style="margin-top: 10px">
			<button id="validate_notes_articles" style="padding: 5px;" value="validate" class="button primary">Validate URLs</button>
		</div>
	</div>

	<div id='fishnotes-csv' style='display:none;'>
		<h4>URLs and notes as comma-separated values</h4>
		<p>Syntax: <b>Language Code,URL,Notes</b><br />
		Example: <b>en,http://www.wikihow.com/Jump,My notes here</b></p>
		Fields containing commas, quotation marks or new lines must be wrapped with quotes, e.g.:<br />
		<b>en,http://www.wikihow.com/Jump,"Note, with, commas"</b><br />
		Quotation marks inside fields must also be doubled up, e.g.:<br />
		<b>en,http://www.wikihow.com/Jump,"Note with ""quote"" marks</b><br />
		<textarea class="urls input_med" rows="500" name="csv" id="csv">
		</textarea>

		<div style="margin-top: 10px">
			<button id="csvNotes" style="padding: 5px;" value="add" class="button primary">Add notes</button>
		</div>
	</div>
<? } else { ?>
	<h4>URLs</h4>
	<textarea class="urls input_med" rows="500" name="urls" id="urls">
	</textarea>

	<div style="margin-top: 10px">
		<button id="clearNotes" style="padding: 5px;" value="clear" class="button primary">Clear notes</button>
	</div>
<? } ?>

<div id='results'></div>

<script>
(function ($) {
	$(document).on('click', '#toggleNoteType', function (e) {
		if ($('#toggleNoteType').val() == 'toggleCSV') {
			$('#fishnotes-normal').hide();
			$('#fishnotes-csv').show();
			$('#toggleNoteType').val('toggleNormal');
			$('#toggleNoteType').html('Switch to normal input');
		} else {
			$('#fishnotes-csv').hide();
			$('#fishnotes-normal').show();
			$('#toggleNoteType').val('toggleCSV');
			$('#toggleNoteType').html('Switch to CSV input');
		}

		return false;
	});
})(jQuery);
</script>
