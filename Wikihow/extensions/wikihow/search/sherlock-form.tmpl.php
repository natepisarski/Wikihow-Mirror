<? // hidden form for posting data to Sherlock ?>
<form id="sherlock-form" action="/Special:Sherlock" method="post" style="display:none">
	<input name="sha_index" value="<?= $index ?>">
	<input name="sha_id" value="<?= $result['id'] ?>">
	<input name="sha_title" value="<?= $result['title'] ?>">
	<input name="shs_key" value="<?= $searchId ?>">
</form>
