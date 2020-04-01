<?= wfMessage('clearreating_reason_restore') ?>
<br />
<br />
<form  id='clear_ratings' method='POST' action='<?= $postUrl ?>'>
	<?= wfMessage('clearratings_reason') ?> <input type='text' name='reason' size='40'>
	<br />
	<br />
	<?php foreach ($params as $k=>$v): ?>
		<input type='hidden' value='<?= $v ?>' name='<?= $k ?>' />
	<?php endforeach ?>
	<input type='submit' value='<?= wfMessage('clearratings_submit')?>'/>
</form>
