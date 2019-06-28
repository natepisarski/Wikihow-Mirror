<div class="wh_block">
	<h1><?= wfMessage('ar_page_title')->text() ?></h1>
	<p><?= wfMessage('ar_subtitle')->text() ?></p>
</div>
<? foreach ($expertCategories as $catname => $category): ?>
	<div class="minor_section ar_category">
		<h3><a class="ar_anchor" name="<?= strtolower($catname) ?>"></a><?= $catname ?></h3>

		<? $i = 0; ?>
		<? foreach ($category as $name => $expert): ?>
			<? if ($name == "count") continue; ?>
			<? if ($i % $numRows == 0): ?>
				<div class="ar_row">
			<? endif; ?>
			<div class="reviewer">
				<a class="ar_anchor" name="<?= $expert['expert']->anchorName ?>"></a>
				<div class="ar_initials"><div class="ar_avatar" style="background-image: url('<?= $expert['expert']->imagePath ?>');"></div><span><?= $expert['expert']->initials ?></span></div>
				<div class="reviewer_top">
					<p class="ar_name">
						<? if ($expert['expert']->nameLink != ""): ?>
							<a href="<?= $expert['expert']->nameLink ?>" rel="nofollow" class="external" target="_blank"><?= $expert['expert']->name ?></a>
						<? else: ?>
							<?= $expert['expert']->name ?>
						<? endif; ?>
					</p>
					<!--<p><?= $expert['count']?></p>-->
					<p class="ar_blurb"><?= $expert['expert']->blurb ?></p>
					<?= $expert['expert']->nameLinkHTML ?>
				</div>
				<p class="ar_hoverblurb"><?= $expert['expert']->hoverBlurb ?></p>
			</div>

			<?
				$i++;
				if ($i % $numRows == 0): ?>
					</div>
				<? else: ?>
					<div class="ar_spacer"></div>
				<? endif;
			?>
		<? endforeach; ?>
		<? if ($i % $numRows != 0): ?>
			</div>
		<? endif; ?>
	</div>
<? endforeach ?>
