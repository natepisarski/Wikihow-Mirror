<? if (!empty($articles)) { ?>
<? foreach ($articles as $i => $a) { ?>
<tr>
	<td ><?=$a->getPageId()?></td>
	<td class='urlcol'><?=$linker->linkWikiHowUrl($a->getUrl())?></td>
	<td><?=implode(", ", $a->getTopLevelCategories())?></td>
	<td><a href='#' class='reserve' langcode='<?=$a->getLangCode()?>' aid='<?=$a->getPageId()?>'>reserve article</a></td>
	<td class='notescol'><?=$a->getNotes()?></td>
</tr>
<? 
	}
}  
?>
