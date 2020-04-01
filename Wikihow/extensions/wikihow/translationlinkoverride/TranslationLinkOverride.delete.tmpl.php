<div style="font-size: 13px; margin: 20px 0 7px 0;">
</div>
<label for="lang">Language</label><select id="lang">
	<?php foreach ($langs as $lang) { ?>
	<option value="<?php print $lang; ?>">
		<?php print $lang; ?>
	</option>
	<?php } ?>
</select>
<label for="id">Article ID</label><input type="text" id="article_id"><button id="searchBtn">Search</button>
<br /><br /><br />
<hr />
<table id="langTable">
	<thead>
		<tr>
			<td>From Language</td>
			<td>From Article Id</td>
			<td>From URL</td>
			<td>To Lang</td>
			<td>To URL</td>
			<td>To Article Id</td>
		</tr>
	</thead>
	<tbody>
	</tbody>
</table>
