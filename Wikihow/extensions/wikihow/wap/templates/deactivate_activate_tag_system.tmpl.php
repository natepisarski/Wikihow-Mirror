<?=$css?>
<?=$js?>
<?=$nav?>
<h4>Tags to <?=$title?></h4>
<div class="select_container"  style="margin-top: 10px">
	<select name='tags' id='tags' data-placeholder="Select a tag" style="width:350px;" multiple class="tags" tabindex="8">
	<?
	if (sizeof($tags)) {
		echo $linker->makeTagSelectOptions($tags);
	}
	?>
	</select>
</div>
<div style="margin-top: 10px">

<button id="<?=$buttonId?>" style="padding: 5px;" value="<?=$buttonId?>"><?=$title?> Tags</button>
</div>
<div id='results'></div>
