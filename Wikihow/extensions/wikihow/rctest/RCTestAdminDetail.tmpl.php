<table class="rct_scores">
	<tr>
		<th>Test</th>
		<th>Difficulty</th>
		<th>Response</th>
		<th>Correct</th>
		<th>Date</th>
	</tr>
	<? 
	foreach ($results as $i => $result): 
		$class = $i % 2 == 0 ? 'even' : 'odd';
	?>
		<tr class="<?=$class?>">
			<td><a href='http://www.wikihow.com/Special:RCPatrol?rct_mode=carrot&rct_id=<?=$result['rs_quiz_id']?>' target='_new'>Test <?=$result['rs_quiz_id']?></a></td>
			<td><?=$result['rq_difficulty']?></td>
			<td><?=$result['rs_response']?></td>
			<td><?=$result['rs_correct']?></td>
			<td><?=$result['rs_timestamp']?></td>
		</tr>
	<? endforeach; ?>
</table>
