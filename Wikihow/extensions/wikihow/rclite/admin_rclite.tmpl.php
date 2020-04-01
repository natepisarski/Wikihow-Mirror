<style>

	.rcl_active_patrollers {
		width: 50%;
		margin-bottom: 20px;
	}

	table.rcl_active_patrollers th {
		padding: 5px;
		text-align: left;
	}

	tr {
		background: white;
	}

	tr:hover {
		background: #b7d6ed;
	}

	table.rcl_active_patrollers td {
		padding: 10px;
	}

	td.rcl_patrol_count {
		width: 10%;
	}

	.rcl_active_patrollers td {
		padding: 5px;
	}

	h2 {
		margin-top: 20px;
	}


</style>
<h2><?=$title;?></h2>
<table class="rcl_active_patrollers">
	<thead>
	<th>Patroller</th>
	<th>Count</th>
	</thead>
	<? foreach ($data as $datum):?>
		<tr class="rcl_patrollers_row">
			<td class="rcl_user_link"><?=$datum['link']?></td>
			<td class="rcl_patrol_count"><?=$datum['cnt']?></td>
		</tr>
	<? endforeach ?>
</table>
