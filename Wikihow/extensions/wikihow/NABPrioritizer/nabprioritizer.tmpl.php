<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<script type="text/javascript">
$(document).ready(function() {
	$("#dropdown").change(function() {
		location = "/Special:NABPrioritizer?algorithm=" + $("#dropdown").val();
	});
});
</script>
<h3>Algorithm</h3>
<select id="dropdown">
<?php foreach($nums as $num) { ?>
<option value="<?= $num ?>" <?php if($num==$algorithm) { ?>selected <?php } ?>><?= $num ?></option>
<?php } ?>
</select>

<h2>Articles to be Deleted</h2>
<table>
<thead><tr><td>Day Added</td><td>Title</td><td>Article ID</td><td>Revision ID</td><td>Deletion Score</td><td>Not a how to Deletion Score</td><td>Stub Score</td></tr></thead>
<? foreach($rows as $row) { 
    $linkTitle = str_replace(" ","-",$row->an_page_title);
?>
<tr><td><?= $row->an_day ?></td><td><a href="http://www.wikihow.com/<?= $linkTitle ?>"><?= $row->an_page_title ?></a></td><td><?= $row->an_page_id ?></td><td><a href="http://www.wikihow.com/index.php?title=<?= $linkTitle ?>&oldid=<?= $row->an_revision_id ?>"><?= $row->an_revision_id?></a></td><td><?= $row->an_dscore ?></td><td><?= $row->an_ndscore ?></td><td><?= $row->an_sscore ?></tr>
<? } ?>
</table>
