<div id='image_stack_outer'>
	<div id='image_stack'>
		<table>
		<tr>
		<td>
			<div style="width:14px;height:15px;">
			<a href="<?= $imageUrl[0] ?>" title="<?= $imageTitle[0] ?>" class="image">
				<img border="0" src="<?= wfGetPad('/skins/WikiHow/images/is_left.png') ?>" alt="<?= $imageTitle[0] ?>">
			</a>
			</div>
		</td>
		<?for ($i = 0; $i < $numImages && $i < 5; $i++) { ?>
			<td>
			<div style="width:<?= $imageWidth[$i] + 10 ?>px;height:<?= $imageHeight[$i] + 10 ?>px">
			<a href="<?= $imageUrl[$i] ?>" title="<?= $imageTitle[$i] ?>" class="image">
				<img class="image_stack_shadow" src="<?= wfGetPad($thumbUrl[$i]) ?>" alt="<?= $imageTitle[$i] ?>">
			</a>
			</div>
			</td>
		<?}?>
		<td style='padding-left:0px;padding-bottom:15px;'>
			<div style="width:14px;height:15;">
				<a href="<?= $imageUrl[$i - 1] ?>" title="<?= $imageTitle[$i - 1] ?>" class="image">
					<img border="0" src="<?= wfGetPad('/skins/WikiHow/images/is_right.png') ?>" alt="<?= $imageTitle[$lastPos] ?>">
				</a>
			</div>
		</td>
		</tr>
		</table>
	</div>
</div>
