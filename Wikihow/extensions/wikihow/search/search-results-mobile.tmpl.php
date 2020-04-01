<?php if (!$q): return; endif; ?>

<? if ($suggestionLink): ?>
	<div class="sr_suggest"><?= wfMessage('lsearch_suggestion', $suggestionLink)->text() ?></div>
<? endif; ?>

<? if (!$results): ?>
	<div class="search_no_results_container">
		<?= wfMessage('lsearch_no_results_for', $enc_q)->inContentLanguage()->text() ?>
	</div>
<? endif; ?>

<?= $ads; ?>

<div id="search_adblock_top" class="search_adblock"></div>

<?php if (!$results): return; endif; ?>

<div id='searchresults_list' class='wh_block'>

	<?
	$noImgCount = 0;
	foreach($results as $i => $result):
		$no_result = '';
		$thumb = '';

		if (!empty($result['img_thumb_250'])) {
			$thumb = 'background-image: url('.$result['img_thumb_250'].')';
		}
		else {
			$no_result = $noImgCount ++ % 2 == 0 ? 'no_result_green' : 'no_result_blue';
		}
		if ($i == 5) {
			echo '<div id="search_adblock_middle" class="search_adblock"></div>';
		}
		if (!$result['is_category']) {
			$result_title = $result['title_match'];
		}
		else {
			$result_title = wfMessage('lsearch_article_category', $result['title_match']);
		}
		if (!(class_exists('AndroidHelper') && AndroidHelper::isAndroidRequest() && $result['is_category'])):
	?>
		<? $url = $result['url']; ?>
		<?
			if (!preg_match('@^http:@', $url)) {
				$url = $BASE_URL . '/' . $url;
			}
		?>
		<a class="result_link" href=<?= $url ?> >
			<div class="result">
				<div class='result_thumb <?= $no_result ?>' style='<?= $thumb ?>'>
					<? if ($no_result != '') echo $howTo ?>
				</div>
				<div class="result_data">
					<div class="result_title"><?= $result_title ?></div>
				<? if ($result['has_supplement']): ?>
					<ul class="search_results_stats">
						<li class="sr_view">
							<?=wfMessage('lsearch_views', number_format($result['popularity']))->text();?>
						</li>
						<li class="sr_updated">
							<span><?= $updated ?></span>
							<?=wfTimeAgo(wfTimestamp(TS_UNIX, $result['timestamp']), true);?>
						</li>
						<? if (class_exists('SocialProofStats') && $result['verified']): ?>
							<li class="sp_verif">
								<?= str_replace('<br>',' ', SocialProofStats::getIntroMessage($result['verified'])) ?>
							</li>
						<? endif ?>
					</ul>
				<? endif; // has_supplement ?>
				<? // Sherlock-form ?>
				<?= EasyTemplate::html(
					'sherlock-form.tmpl.php',
					[ 'index' => $i + $first, 'result' => $result, 'searchId' => $searchId ]
				); ?>
				</div>
			</div>
		</a>
	<?
		endif;
	endforeach;
	?>
	<div id="search_adblock_bottom" class="search_adblock"></div>
</div>

<?php
if (($total > $start + $max_results
		&& $last == $start + $max_results)
	|| $start >= $max_results):
		$resultsMsg = class_exists('AndroidHelper') && AndroidHelper::isAndroidRequest() ? 'lsearch_results_range_android' : 'lsearch_results_range';
?>

	<div id='searchresults_footer'>
		<?=$next_button.$prev_button?>
		<div class="sr_foot_results"><?= wfMessage($resultsMsg, $first, $last, number_format($total)) ?></div>
		<div class="sr_text"><?= wfMessage('lsearch_mediawiki', $specialPageURL . "?search=" . urlencode($q)) ?></div>
	</div>

<? endif; ?>

<!-- search results source: <?= $results_source ?> -->
