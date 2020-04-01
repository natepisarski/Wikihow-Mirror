<strong style="color:#ff0000"><?= $err ?></strong>
<hr size='1'/>
	<br/>
	<form id='ratings' method='GET' action='<?= $actionUrl ?>'>
		<?php wfMessage('clearratings_input_title') ?>
		<input type='text' name='target' value='<?= $target ?>'>
		<select name="type">
			<option value='article' <?= $type =='article'?"selected='selected'":""; ?>>Article</option>
			<option value='sample' <?= $type =='sample'?"selected='selected'":""; ?>>Sample</option>
		</select>
		<input type=submit>
</form>
