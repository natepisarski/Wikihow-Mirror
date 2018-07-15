<?=$css?>
<?=$js?>
<?=$nav?>
<h4>WARNING: Removing an User removes all article assignments to the user within the system</h4>
<h4>User to Remove</h4>
<div class="select_container" style="margin-top: 10px">
	<select name='users' id='users' data-placeholder="Select one or more users to remove" style="width:350px;" tabindex="8">
	<?
		if (sizeof($users)) {
			echo $linker->makeUserSelectOptions($users);
		}
	?>
	</select>
</div>

<div style="margin-top: 10px">
<button id="remove_users" style="padding: 5px;" value="remove_users">Remove Users</button>
</div>
<div id='results'></div>
