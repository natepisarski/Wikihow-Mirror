<p>Drag and drop to reorder the articles.</p>
<p>Note: inactive ones go to the bottom</p>
<form action="/Special:WikihowHomepageAdmin" method="post" name="updating">
	<input type="hidden" name="updateActive" value="1" />
	<table>
		<thead>
			<tr style="text-align: left;">
				<th>Title</th>
				<th>Image<br />(1280px x 768px)</th>
				<th>Active</th>
				<th></th>
			</tr>
		</thead>
		<tbody class="hp_admin_box">
			<?php foreach($items as $item): ?>
				<tr>
					<td><a href="<?=$item->title->getLocalUrl()?>" target="_blank"><?= $item->title->getText()?></a></td>
					<td><img src="<?= $item->file ?>" /></td>
					<td><input type="checkbox" name="hp_images[]" value="<?= $item->hp_id ?>" <? if($item->hp_active==1) echo "checked='checked'"; ?> /></td>
					<td><input type="button" value="Delete" id="delete_<?= $item->hp_id ?>" class="hp_delete" /></td>
				</tr>
			<?php endforeach; ?>
			<tr>
				<td colspan="4"><input type="submit" value="Update active items"/> </td>
			</tr>
		</tbody>
	</table>
</form>
