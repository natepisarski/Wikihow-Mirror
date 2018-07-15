<?
	// refactor: set vars if $q == empty
	if ($q == null):
		return;
	endif;
?>

<? if (count($results) > 0): ?>
	<div class='result_count'><?= wfMessage('lsearch_results_range_alt', $first, $last, $total)->text() ?></div>
	<h2><?= wfMessage('lsearch_results_for', $enc_q)->inContentLanguage()->text() ?></h2>
<? endif; ?>

<? if ($suggestionLink): ?>
	<div class="sr_suggest"><?= wfMessage('lsearch_suggestion', $suggestionLink)->text() ?></div>
<? endif; ?>
<? if (count($results) == 0): ?>
	<div class="sr_noresults"><?= wfMessage('lsearch_noresults', $enc_q) ?></div>
	<div id='searchresults_footer'><br /></div>
	<? return; ?>
<? endif; ?>

<div id='searchresults_list' class='wh_block'>
<?=$ads;?>
<? foreach($results as $i => $result): ?>
	<div class="result">
		<? if (!$result['is_category']): ?>
			<? if (!empty($result['img_thumb_100'])): ?>
				<div class='result_thumb'><img src="<?= $result['img_thumb_100'] ?>" /></div>
			<? endif; ?>
		<? else: ?>
			<div class='result_thumb cat_thumb'><img src="<?= $result['img_thumb_100'] ? $result['img_thumb_100'] : '/skins/WikiHow/images/Book_75.png' ?>" /></div>
		<? endif; ?>

<?
	$url = $result['url'];
	if (!preg_match('@^http:@', $url)) {
		$url = $BASE_URL . '/' . $url;
	}
?>

		<? if ($result['has_supplement']): ?>
			<? if (!$result['is_category']): ?>
				<a href="<?= $url ?>" class="result_link"><?= $result['title_match'] ?></a>
			<? else: ?>
				<a href="<?= $url ?>" class="result_link"><?= wfMessage('lsearch_article_category', $result['title_match']) ?></a>
			<? endif; ?>

			<? if (!empty($result['first_editor'])): ?>
				<div>
					<?
						$editorLink = Linker::link(Title::makeTitle(NS_USER, $result['first_editor']), $result['first_editor']);
					?>
					<? if ($result['num_editors'] <= 1): ?>
						<?= wfMessage('lsearch_edited_by', $editorLink) ?>
					<? elseif ($result['num_editors'] == 2): ?>
						<?= wfMessage('lsearch_edited_by_other', $editorLink, $result['num_editors'] - 1) ?>
					<? else: ?>
						<?= wfMessage('lsearch_edited_by_others', $editorLink, $result['num_editors'] - 1) ?>
					<? endif; ?>
				</div>

				<? if (!empty($result['last_editor']) && $result['num_editors'] > 1): ?>
					<div>
						<?= wfMessage( 'lsearch_last_updated', wfTimeAgo(wfTimestamp(TS_UNIX, $result['timestamp']), true), Linker::link(Title::makeTitle(NS_USER, $result['last_editor']), $result['last_editor']) ) ?>
					</div>
				<? endif; ?>
			<? endif; ?>

			<ul class="search_results_stats">
				<? if ($result['is_featured']): ?>
					<li class="sr_featured"><?= wfMessage('lsearch_featured') ?></li>
				<? endif; ?>
				<? if ($result['has_video']): ?>
					<li class="sr_video"><?= wfMessage('lsearch_has_video') ?></li>
				<? endif; ?>
				<? if ($result['steps'] > 0): ?>
					<li class="sr_steps"><?= wfMessage('lsearch_steps', $result['steps']) ?></li>
				<? endif; ?>

				<li class="sr_view">
				<? if ($result['popularity'] < 100): ?>
					<?= wfMessage('lsearch_views_tier0') ?>
				<? elseif ($result['popularity'] < 1000): ?>
					<?= wfMessage('lsearch_views_tier1') ?>
				<? elseif ($result['popularity'] < 10000): ?>
					<?= wfMessage('lsearch_views_tier2') ?>
				<? elseif ($result['popularity'] < 100000): ?>
					<?= wfMessage('lsearch_views_tier3') ?>
				<? else: ?>
					<?= wfMessage('lsearch_views_tier4') ?>
				<? endif; ?></li>
			</ul>
		<? else: ?>
			<a href="<?= $url ?>" class="result_link"><?= $result['title_match'] ?></a>
		<? endif; // has_supplement ?>

		<div class="clearall"></div>
	</div>
<? endforeach; ?>
</div>

<?
if (($total > $start + $max_results
	  && $last == $start + $max_results)
	|| $start >= $max_results): ?>

<div id='searchresults_footer'>
	<?=$next_button.$prev_button?>
	<div class="sr_foot_results"><?= wfMessage('lsearch_results_range', $first, $last, $total) ?></div>
	<div class="sr_text"><?= wfMessage('lsearch_mediawiki', $specialPageURL . "?search=" . urlencode($q)) ?></div>
</div>

<? endif; ?>
