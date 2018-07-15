<h2> Contributions to <a href="/<?= $pageTitle ?>"><?= str_replace('-',' ',$pageTitle)?></a>: </h2>
<table>
<thead><tr><td>Page Title (Revision ID)</td><td>Contributor</td><td>Comment</td><td>Bytes</td></tr></thead>
<tbody>
<?php foreach($contributions as $contribution) { ?>
	<tr><td><a href="/index.php?title=<?=$contribution['page_title']?>&oldid=<?= $contribution['rev_id']?>"><?= str_replace("-"," ",$contribution['page_title']) ?> (<?= $contribution['rev_id']?>)</a></td><td><?= $contribution['user_name']?></td><td><?= $contribution['comment']?></td><td><?= $contribution['bytes'] ?></td></tr>
<?php } ?>
</tbody>
</table>
