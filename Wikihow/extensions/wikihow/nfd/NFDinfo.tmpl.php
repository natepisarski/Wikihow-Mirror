<table cellpadding="0" cellspacing="0" id="nfd_article_info" class="section_text">
	<tr>
		<td class="first">Created:</td><td><?= $age ?></td>
	</tr>
	<tr>
		<td class="first">Popularity:</td><td><?= $views ?> views</td>
	</tr>
	<tr>
		<td class="first">Article Info:</td><td><?= $edits ?> <?= $edits == 1?"edit":"edits"; ?>, <?= $discussion ?> <a href="#" class="discuss_link">discussion page</a> <?= $discussion == 1?"message":"messages"; ?></td>
	</tr>
	<tr>
		<td class="first">Author:</td><td><a href='<?= $authorUrl ?>'><?= $authorName ?></a>, <?= $userEdits ?> <?= $userEdits == 1?"edit":"edits"; ?></td>
	</tr>
	<tr>
		<td class="first">NFD Reason:</td><td><?= $nfd ?></td>
	</tr>
	<tr>
		<td class="first">NFD Votes:</td><td><?= $nfdVotes ?></td>
	</tr>
</table>
