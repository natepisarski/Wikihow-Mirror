<? if (!empty($rows)) { ?>
<div id='tag_row_data'>
	<table class='wap'>
	<thead>
		<th>Id</th>
		<th>Url</th>
		<th>Categories</th>
		<th>Action</th>
		<th>Notes</th>
	</thead>
	<?=$rows?>
	</table>
	<div id='tag_list_nav'>
		<a href='#' id='tag_list_more_rows' cid='<?=$tag?>' offset='<?=$offset + $numrows?>' numrows='<?=$numrows?>'>[Click Here to See Next 500 Articles]</a>
	</div>
</div>
<? } ?>
