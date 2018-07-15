<h2><?=wfMessage('ar_sb_header')->text()?></h2>
<div class="ar_apply"><?= wfMessage('ar_apply')->text() ?></div>
<div class="ar_sidebar_item selected" data-anchor="all">View All (<?=$expert_count?>)</div>
<? foreach($expertCategories as $catname => $category): ?>
	<? if ($catname == "experts" ) continue; ?>
	<div class="ar_sidebar_item" data-anchor="<?= strtolower($catname) ?>"><?=$catname?> (<?=(count($category) - 1)?>)</div>
<? endforeach ?>
