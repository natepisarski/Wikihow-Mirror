<?= $message ?>
<h3><?= wfMessage("spch-articlelist-add") ?></h3>
<form action="/Special:SpellcheckerArticleWhitelist" method="POST">
	<?= wfMessage("spch-articlelist-url") ?> 
	<input type="text" name="articleName" style="width:200px" />
	<input type="submit" value="Add Article" />
</form>
<br /><br />
<h3><?= wfMessage("spch-articlelist-current") ?></h3>
