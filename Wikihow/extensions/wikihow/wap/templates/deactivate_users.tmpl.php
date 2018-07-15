<?=$css?>
<?=$js?>
<?=$nav?>
<h4>WARNING: Deactivating a user prevents a user from accessing the system but will keep all completed articles by the user within the database</h4>
<h4>User to Deactivate</h4>
<div class="select_container" style="margin-top: 10px">
	<select name='users' id='users' data-placeholder="Select one or more users to deactivate" style="width:350px;" tabindex="8">
	<?
		if (sizeof($users)) {
			echo $linker->makeUserSelectOptions($users);
		}
	?>
	</select>
</div>

<div style="margin-top: 10px">
<button id="deactivate_users" style="padding: 5px;" value="remove_users">Deactivate User</button>
</div>
<div id='results'></div>
