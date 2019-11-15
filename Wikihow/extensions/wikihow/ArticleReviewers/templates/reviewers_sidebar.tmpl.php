<h2><?=wfMessage('ar_sb_header')->text()?></h2>
<div class="ar_sidebar_item selected" data-anchor="all"><?= $view_all ?> (<?=$expert_count?>)</div>
<? foreach($expertCategories as $catname => $category): ?>
	<div class="ar_sidebar_item" data-anchor="<?= strtolower($catname) ?>"><?=$catname?> (<?=(count($category) - 1)?>)</div>
<? endforeach ?>
