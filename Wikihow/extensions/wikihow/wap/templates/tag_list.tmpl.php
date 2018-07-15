<?=$css?>
<?=$js?>
<?=$nav?>
<? 
if ($u->isAdmin()) { 
	$tagname = htmlspecialchars($tag['raw_tag'], ENT_QUOTES);
	$tagid = $tag['tag_id'];
?>
<h3>Admin Functions</h3>
<a href='#' id='rpt_tag_articles' tagid='<?=$tagid?>' tagname='<?=$tagname?>'>Article Report</a>
<h3>Visible To</h3>
<?
if (sizeof($users)) {
	echo implode(", ", $linker->linkUsers($users));
}
?>
<br>
<? } ?>
<h3>
	<div class='filter_container'>
		<label for="list_filter">Filter by</label>
		<select name='list_filter' id='list_filter' data-placeholder="Select a Category" style="width:200px;" tabindex="1">
		<?  echo $linker->makeCategoriesSelectOptions($cats); ?>
		</select>
	</div>
	Articles
</h3>
<?=$articles?>
