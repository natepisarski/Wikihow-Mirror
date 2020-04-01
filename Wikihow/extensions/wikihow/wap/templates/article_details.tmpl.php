<div id='tag_row_data'>
	<table class='wap'>
	<thead>
		<th>Id</th>
		<th>Url</th>
		<th>Lang</th>
		<th>Tags</th>
		<th>Categories</th>
		<th>Action</th>
		<th>Notes</th>
	</thead>
	<tr>
		<td ><?=$a->getPageId()?></td>
		<td class='urlcol'><?=$linker->linkWikiHowUrl($a->getUrl())?></td>
		<td ><?=$a->getLangCode()?></td>
		<td class='tagcol'><?=implode(", ", $linker->linkTags($a->getViewableTags($cu)))?></td>
		<td><?=implode(", ", $a->getTopLevelCategories())?></td>
		<td><a href='#' class='reserve' langcode='<?=$a->getLangCode()?>' aid='<?=$a->getPageId()?>'>reserve article</a></td>
		<td><?=$a->getNotes()?></td>
	</tr>
	</table>
</div>
