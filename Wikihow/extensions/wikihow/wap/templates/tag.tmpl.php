<?=$css?>
<?=$js?>
<?=$nav?>
<h4>URLs</h4>
<textarea class="urls input_med" rows="500" name="urls" id="urls">
</textarea>
<? $actionText = $add ? ' to Apply' : ' to Remove'?>
<h4>Tags <?=$actionText?></h4>
<div class='select_container' style="margin-top: 10px">
	<select name='tags' id='tags' data-placeholder="Select a tag" style="width:350px;" multiple class="tags input_med" tabindex="8">
	<?
	if (sizeof($tags)) {
		echo $linker->makeTagSelectOptions($tags);
	}
	?>
	</select>
</div>
<div style="margin-top: 10px">
<? if ($add) { ?>
	<button id="validate_tag_articles" style="padding: 5px;" value="validate" class='button primary'>Validate Urls</button>
<? } else { ?>
	<button id="removeTags" style="padding: 5px;" value="removeTages" class='button primary'>Remove Tags</button>
<? } ?>
</div>
<div id='results'></div>
