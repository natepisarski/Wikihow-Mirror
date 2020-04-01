<?=$css?>
<?=$js?>
<?=$nav?>
<h4>URLs</h4>
<textarea class="urls input_med" rows="500" name="urls" id="urls">
</textarea>
<? if (sizeof($langs) > 1) {?>
	<h4>Language</h4>
	<select name='langcode' id='langcode' data-placeholder="Select a Language" class="chzn-select">
	<?
		echo $linker->makeLanguageSelectOptions($langs);
	?>
	</select>
<? } else { // If it's just one language, select it?>
	<input type="hidden" name="langcode" id="langcode" value="<?=$langs[0]?>"/>
<? }?>
<div style="margin-top: 10px">
	<button id="rpt_custom" style="padding: 5px;" class='button primary'>Get Report</button>
</div>
