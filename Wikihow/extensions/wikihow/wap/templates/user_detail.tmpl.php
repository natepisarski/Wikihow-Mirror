<?=$css?>
<?=$js?>
<?=$nav?>
Welcome to <?=$system?>, <?=$u->getName()?>! You can use this tool to browse and reserve articles on the "Article Selection Lists". 
<br>
Articles you reserve can be found under "Your Reserved Articles".
<? if ($admin) { ?>
<h3>Admin Functions</h3>
<a href='#' id='rpt_user_articles' uname='<?=$u->getName()?>' uid='<?=$u->getId()?>'>Article Report</a>
<? } ?>
<h3>Article Selection Lists</h3>
<?$tags = $linker->linkTags($u->getTags())?>
<ul>
<? foreach ($tags as $tag) { ?>
<li><?=$tag?></li>
<? } ?>
</ul>
<br>
<h3><a class='c_refresh' href='/Special:<?=$system?>'>refresh</a>Your Reserved Articles</h3>
<?=$assigned?>
<br>
<h3>Your Recently Completed Articles</h3>
<? if (sizeof($completed)) {?>
<table class='wap tablesorter'>
<thead>
	<th>Id</th>
	<th>Url</th>
	<th>Completed Date</th>
	<th>Notes</th>
</thead>
<tbody>
	<? foreach ($completed as $a) { ?>
	<tr>
		<td><?=$a->getPageId()?></td>
		<td>
		<?=$linker->linkSystemUrl($a->getUrl())?>
		</td>
		<td><?=$a->getCompletedDate()?></td>
		<td><?=$a->getNotes()?></td>
	</tr>
	<? } ?>
</tbody>
</table>
<? } ?>
<br>
<? if ($myProfile) { ?>
<h3>Search for Article</h3>
<div>
<label for='url'>URL<label> <input class='input_med' id='url' type='text' name='url'/>
<input type='button' id='article_details' name='details' class='button primary' value='Find'>
</div>
<div id='results'></div>
<? } ?>
