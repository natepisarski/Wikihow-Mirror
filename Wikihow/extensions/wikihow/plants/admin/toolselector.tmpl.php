<form method='post' action="#" id="tool_select_form">
	<select id="tool_select">
		<option>Select Tool</option>
		<?php foreach($tools as $tool): ?>
			<option value="<?= $tool ?>"><?= $tool ?></option>
		<? endforeach ?>
	</select>
</form>
