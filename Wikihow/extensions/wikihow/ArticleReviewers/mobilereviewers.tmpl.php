<div class="wh_block ar_intro">
	<h1>Article Reviewers</h1>
	<p><?= wfMessage('ar_subtitle')->text() ?> <?= wfMessage('ar_apply_mobile')->text() ?></p>
</div>
<? foreach($expertCategories as $catname => $category): ?>
	<? if ($catname == "experts" ) continue; ?>
	<div class="minor_section ar_category">
		<h3><a class="ar_anchor" name="<?= strtolower($catname) ?>"></a><?= $catname ?></h3>

		<? foreach($category as $name => $expert): ?>
			<? if ($name == "count") continue; ?>
			<div class="reviewer">
				<a class="ar_anchor" name="<?= $expert['expert']->anchorName ?>"></a>
					<? if ($expert['expert']->nameLink != ""): ?>
						<a href="<?= $expert['expert']->nameLink ?>" class="ar_more_icon" target="_blank"></a>
					<? endif; ?>
				<div class="ar_initials"><div class="ar_avatar" style="background-image: url('<?= $expert['expert']->imagePath ?>');"></div><span><?= $expert['expert']->initials ?></span></div>
				<div class="reviewer_top">
					<p class="ar_name"><?= $expert['expert']->name ?></p>
					<!--<p><?= $expert['count']?></p>-->
					<p class="ar_blurb"><?= $expert['expert']->blurb ?></p>
					<?= $expert['expert']->nameLinkHTML ?>
				</div>
				<p class="ar_hoverblurb">
					<?= $expert['expert']->hoverBlurb ?>
				</p>
			</div>
		<? endforeach; ?>
		<div class="clearall"></div>
	</div>
<? endforeach ?>
