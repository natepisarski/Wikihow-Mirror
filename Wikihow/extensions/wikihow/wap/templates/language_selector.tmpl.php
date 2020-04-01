<?=$css?>
<?=$js?>
<?=$nav?>
<?=$description?>
<br>
<? if (sizeof($langs) > 1) {?>
	<h4>Language</h4>
	<select name='langcode' id='langcode' class="input_med" data-placeholder="Select a Language" class="chzn-select">
	<?
		echo $linker->makeLanguageSelectOptions($langs);
	?>
	</select>
<? } else { // If it's just one language, select it?>
	<input type="hidden" name="langcode" id="langcode" class="input_med" value="<?=$langs[0]?>"/>
<? }?>
<div style="margin-top: 10px">
	<button id="<?=$buttonId?>" style="padding: 5px;">Get Report</button>
</div>
