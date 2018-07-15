<? if (!empty($articles)) { ?>
	<? foreach ($articles as $a) { ?>
	<tr>
		<td><?=$a->getPageId()?></td>
		<td class='urlcol'><?=$linker->linkWikiHowUrl($a->getUrl())?></td>
		<? $displayUser = $currentUser->isAdmin() ? $currentUser : $u ?>
		<td class='tagcol'><?=implode(", ", $linker->linkTags($a->getViewableTags($displayUser)))?></td>
		<td><?=implode(", ", $a->getTopLevelCategories())?></td>
		<td class='datecol'><?=$a->getReservedDate()?></td>
		<td class='actioncol'><a href='#' class='complete' langcode='<?=$a->getLangCode()?>' aid='<?=$a->getPageId()?>'>mark as done</a> | <a href='#' class='release' langcode='<?=$a->getLangCode()?>' aid='<?=$a->getPageId()?>'>remove from my list</a></td>
		<td class='notescol'><?=$a->getNotes()?></td>
	</tr>
	<? } ?>
<? } ?>
