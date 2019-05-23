<?php if (!$q): return; endif; ?>

<? if ($suggestionLink): ?>
	<div class="sr_suggest"><?= wfMessage('lsearch_suggestion', $suggestionLink)->text() ?></div>
<? endif; ?>

<? if (!$results): ?>
	<div class="search_no_results_container">
		<?= wfMessage('lsearch_no_results_for', $enc_q)->inContentLanguage()->text() ?>
	</div>
	<script type="text/javascript">
			setTimeout(function() {
				$("#bubble_search .search_box, #bubble_search .search_button").css("border", "2px solid white");
				setTimeout(function() {
					$("#bubble_search .search_box, #bubble_search .search_button").css('border', 'none');
				}, 3000);
			}, 1000);
	</script>
<? endif; ?>

<?= $ads; ?>

<div id="search_adblock_top" class="search_adblock"></div>

<?php if (!$results): return; endif; ?>

<div id='searchresults_list' class='wh_block'>

	<?
	$noImgCount = 0;
	foreach($results as $i => $result):

		if (empty($result['img_thumb_250'])) {
			$result['img_thumb_250'] = $noImgCount++ % 2 == 0 ?
				$no_img_green : $no_img_blue;
		}
		if ($i == 5) {
			echo '<div id="search_adblock_middle" class="search_adblock"></div>';
		}
	?>
		<?
		$url = $result['url'];
		if (!preg_match('@^http:@', $url)) {
			$url = $BASE_URL . '/' . $url;
		}
		?>
		<a href="<?= $url ?>" class="result_link">
		<div class="result <?=$i == 0 || $i == 3 ? "result_margin" : "";?>">
			<? if (!$result['is_category']): ?>
				<div class='result_thumb'>
				<? if (!empty($result['img_thumb_250'])): ?>
					<img src="<?= $result['img_thumb_250'] ?>" />
				<? endif; ?>
				</div>
			<? else: ?>
				<div class='result_thumb cat_thumb'><img src="<?= $result['img_thumb_250'] ? $result['img_thumb_250'] : $noImg ?>" /></div>
			<? endif; ?>

			<div class="result_data">
			<? if ($result['has_supplement']): ?>
				<? if (!$result['has_supplement'] || !$result['is_category']): ?>
					<div class="result_title"><?= $result['title_match'] ?></div>
				<? else: ?>
					<div class="result_title"><?= wfMessage('lsearch_article_category', $result['title_match']) ?></div>
				<? endif; ?>
				<div class="result_data_divider"></div>
				<ul class="search_results_stats">
					<li class="sr_view"><span class="sp_circle sp_views_icon"></span>
						<?=wfMessage('lsearch_views', number_format($result['popularity']))->text();?>
					</li>
					<? if (class_exists('SocialProofStats') && $result['verified']): ?>
						<li class="sr_verif"><span class="sp_verif_icon"></span>
							<?= SocialProofStats::getIntroMessage($result['verified']) ?>
						</li>
					<? else: ?>
						<li>&nbsp;</li>
					<? endif ?>
					<li class="sr_updated"><span class="sp_circle sp_updated_icon"></span>
						<?=wfMessage('lsearch_last_updated_desktop', wfTimeAgo(wfTimestamp(TS_UNIX, $result['timestamp']), true));?>
					</li>
				</ul>
			<? else: ?>
				<div class="result_title"><?= $result['title_match'] ?></div>
			<? endif; // has_supplement ?>
			<? // Sherlock-form ?>
			<?= EasyTemplate::html('sherlock-form.tmpl.php', array("index" => $i + $first, "result" => $result)); ?>
			</div>
		</div>
		</a>
	<? endforeach; ?>
	<div id="search_adblock_bottom" class="search_adblock"></div>
</div>

<?php
if (($total > $start + $max_results
		&& $last == $start + $max_results)
	|| $start >= $max_results): ?>

	<div id='searchresults_footer'>
		<?=$next_button.$prev_button?>
		<div class="sr_foot_results"><?= wfMessage('lsearch_results_range', $first, $last, number_format($total)) ?></div>
		<div class="sr_text"><?= wfMessage('lsearch_mediawiki', $specialPageURL . "?search=" . urlencode($q)) ?></div>
	</div>
<? endif; ?>

<!-- search results source: <?= $results_source ?> -->
