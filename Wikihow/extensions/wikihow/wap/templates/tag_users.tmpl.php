<?=$css?>
<?=$js?>
<?=$nav?>
<h4>Users</h4>
<div class="select_container" style="margin-top: 10px">
	<select name='users' id='users' data-placeholder="Select one or more users to tag" style="width:350px;" multiple tabindex="8">
	<?
		if (sizeof($users)) {
			echo $linker->makeUserSelectOptions($users);
		}
	?>
	</select>
</div>

<? $actionText = $add ? ' to Apply' : ' to Remove'?>
<h4>Tags <?=$actionText?></h4>
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
<? if ($add) { ?>
<button id="tag_users" style="padding: 5px;" value="tag_users">Tag Users</button>
<? } else { ?>
<button id="remove_tag_users" style="padding: 5px;" value="remove_tag_users">Remove Tags</button>
<? } ?>
</div>
<div id='results'></div>
