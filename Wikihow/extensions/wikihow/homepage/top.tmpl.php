<div id='hp_container'>
	<?php foreach($items as $item): ?>

	<div class="hp_top" id="hp_top_<?= $item->itemNum ?>" style="<?= ($item->itemNum != 1?"display:none":"")?>">
		<a href="<?= $item->url ?>"></a>
			<div class="hp_text" title="<?= $item->text ?>"></div>
			<img class="hp_image" src="<?= $item->imagePath ?>" />
	</div>
	<?php endforeach; ?>
	<div id="hp_middle">
		<div id="hp_middle2">
		<p class="hp_tag"><?= wfMessage('hp_tag')->text() ?></p>
		<?php if ($howToPrefix = wfMessage('howto_prefix')->showIfExists()): ?>
			<p class="hp_howto"><?= $howToPrefix ?></p>
		<?php endif ?>
		<p class="hp_title"></p>
		<?= $search ?>
		<?= $login ?>
		</div>
	</div>
	<div id='hp_navigation'>
		<? foreach($items as $item): ?>
			<a class='hp_nav <?= ($item->itemNum == 1?"on":"")?>' id='nav_<?= $item->itemNum ?>'></a>
		<? endforeach; ?>
	</div>
</div>
