<script type='text/javascript'>
$('a.rct_detail').live('click', function(e) {
	var id = $(this).attr('id');
	id = id.split('_');
	var title = "ALL Tests Taken by " + $(this).html();
	$('#dialog-box').html('');
	$('#dialog-box').load('/Special:RCTestAdmin?a=detail&uid=' + id[1], function() {
		jQuery('#dialog-box').dialog({
			width: 700,
			height: 400,
			modal: true,
			title: title,
			closeText: 'x',
		});
	});
});
</script>
<form class="rct_ts" method="GET"> 
<label for="days">Test scores from the past </label><input type="text" name="days" value="<?=$days?>"/> days
<input type="submit" maxlength="2" name="submit" value="Go"/>
</form>
<table class="rct_scores">
	<tr>
		<th>User</th>
		<th>% Easy </th>
		<th>% Other </th>
		<th>% Total </th>
		<th>% Incorrect</th>
		<th># Easy Fails</th>
		<th>Total Tests</th>
	</tr>
	<? 
	$i = 0;
	foreach ($results as $result): 
		$class = $i % 2 == 0 ? 'even' : 'odd';
		$i++
	?>
		<tr class="<?=$class?>">
			<td><a href='/Special:Contributions/<?=$result['rs_user_name']?>' target="_blank"><?=$result['rs_user_name']?></a></td>
			<td><?=$result['correct_easy']?></td>
			<td><?=$result['correct_other']?></td>
			<td><?=$result['correct']?></td>
			<td><?=$result['incorrect']?></td>
			<td><?=$result['failed_easy']?></td>
			<td><a href='#' class="rct_detail" id="rct_<?=$result['rs_user_id']?>"><?=$result['total']?></a</td>
		</tr>
	<? endforeach; ?>
</table>
