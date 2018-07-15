<?=$css?>
<?=$js?>
<?=$nav?>
<h4>URLs to <?=$action?></h4>
<textarea class="urls" rows="500" name="urls" id="urls">
</textarea>
<h4>User</h4>
	<select name='users' id='users' data-placeholder="Select a User" class="chzn-select">
	<?
		if (sizeof($users)) {
			echo $linker->makeUserSelectOptions($users);
		}
	?>
	</select>
<div style="margin-top: 10px">
<button id="<?=$buttonId?>" style="padding: 5px;" value="validate">Validate</button>
</div>
<div id='results'></div>
