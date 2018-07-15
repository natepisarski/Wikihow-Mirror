<style>
table.thr th  {
	text-align: left;
	cursor: pointer;
	cursor: hand;
}

table.thr td, th {
	padding: 5px;
	text-align: center;
}

table.thr td.left {
	text-align: left;
}

table.thr tbody tr:hover {
	background-color: #E7E3D7;
}
</style>
<h3>General Stats</h3>
<ul>
	<li>Total Votes: <?=$numVotes?></li>
	<li>Tips and Warnings with Votes: <?=$numTipsWarnings?></li>
	<li>Articles with Votes: <?=$numArticles?></li>
</ul>

<h3>Top 2000 Articles with Most Votes</h3>
<table class='thr'>
<thead>
	<th>Url</th>
	<th>Votes</th>
	<th>Up</th>
	<th>Down</th>
</thead>
<tbody>
<?
foreach ($top100 as $datum) {
	$link = "<a href='http://m.wikihow.com/" . urlencode($datum['page_title']) . "'>{$datum['page_title']}</a> " . 
		"(<a target='_blank' href='/Special:ThumbRatings?a=rank&id=" . $datum['page_id'] . "'>rank</a>)";
?>
	<tr>
		<td class='left'><?=$link?></td>
		<td><?=$datum['up'] + $datum['down']?></td>
		<td><?=$datum['up']?></td>
		<td><?=$datum['down']?></td>
	</tr>
<?
}
?>
</tbody>
</table>
