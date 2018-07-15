<div class="minor_section">
	<? if(!$imgStrip) { ?>
	<h2>More images from <?= $title ?></h2>
	<div class='im-images'>
	<?} else {?>
	<div class='is-images'>
	<?}?>
		<table>
			<tr>
			<? if($imgStrip) { ?>
			<td>
				<a href="<?= $imageUrl[0] ?>" title="<?= $imageTitle[0] ?>" class="image">
					<img border="0" src="<?= wfGetPad('/skins/WikiHow/images/is_left.png') ?>" alt="<?= $imageTitle[0] ?>">
				</a>
			</td>
			<? } ?>
			<?for ($i = 0; $i < $numImages && $i < 5; $i++) { ?>
				<td>
					<a href="<?= $imageUrl[$i] ?>" title="<?= $imageTitle[$i] ?>" class="image">
						<img border="0" class="mwimage101" src="<?= wfGetPad($thumbUrl[$i]) ?>" alt="<?= $imageTitle[$i] ?>">
					</a>
				</td>
			<?}?>
			<? if($imgStrip) { 
				$lastPos = $numImages - 1;
			?>
			<td style='padding-left:0px;padding-bottom:5px;'>
				<a href="<?= $imageUrl[$lastPos] ?>" title="<?= $imageTitle[$lastPos] ?>" class="image">
					<img border="0" src="<?= wfGetPad('/skins/WikiHow/images/is_right.png') ?>" alt="<?= $imageTitle[$lastPos] ?>">
				</a>
			<? } ?>
			</td>
			</tr>
		</table>
	</div>
</div>